<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Audit;
use Crms\Auth;
use Crms\Database;
use Crms\Money;
use Crms\Phone;
use Crms\Rbac;
use InvalidArgumentException;
use PDO;
use RuntimeException;

final class HdevPaymentService
{
    public static function assertBookingPaymentAllowed(int $reservationId): void
    {
        $booking = Database::fetch(
            'SELECT status, approval_note, approver_signature, customer_signature FROM reservations WHERE reservation_id = ?',
            [$reservationId]
        );
        if (!$booking || $booking['status'] !== 'confirmed' || trim((string) ($booking['approval_note'] ?? '')) === ''
            || empty($booking['approver_signature']) || empty($booking['customer_signature'])) {
            throw new InvalidArgumentException('Payment is available only after the signed reservation request has a signed approval decision.');
        }
    }

    public function initiate(array $input): array
    {
        if (!Auth::check()) {
            throw new InvalidArgumentException('Sign in before initiating a payment.');
        }
        $requestedCustomerId = (int) ($input['customer_id'] ?? 0);
        if (Auth::isCustomer()) {
            if (!$requestedCustomerId || $requestedCustomerId !== (int) Auth::customerId()) {
                throw new InvalidArgumentException('Customers can pay only their own invoices and reservations.');
            }
        } elseif (!Rbac::can('transactions.manage') && !Rbac::can('invoices.view')) {
            throw new InvalidArgumentException('You are not authorized to initiate customer payments.');
        }
        $type = $input['transaction_type'] ?? 'payment';
        if (!in_array($type, ['payment', 'deposit_received'], true)) {
            throw new InvalidArgumentException('Mobile Money can only initiate a payment or deposit receipt.');
        }
        $amount = Money::round($input['amount'] ?? 0);
        if ((float) $amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }
        if ((float) $amount !== (float) round((float) $amount)) {
            throw new InvalidArgumentException('HDEV Mobile Money payments must be a whole RWF amount.');
        }
        $tel = Phone::hdevTel((string) ($input['phone'] ?? ''));
        if ($tel === null) {
            throw new InvalidArgumentException('Enter a valid Rwanda mobile number for MoMo (07xxxxxxxx).');
        }

        $txRef = 'CRMS-' . strtoupper(bin2hex(random_bytes(6)));
        $row = Database::transaction(function (PDO $pdo) use ($input, $type, $amount, $txRef) {
            $invoiceId = !empty($input['invoice_id']) ? (int) $input['invoice_id'] : null;
            $reservationId = !empty($input['reservation_id']) ? (int) $input['reservation_id'] : null;
            if ($type === 'payment' && !$invoiceId) {
                throw new InvalidArgumentException('Select an issued invoice for a Mobile Money payment.');
            }
            if ($type === 'deposit_received') {
                if ($invoiceId || !$reservationId) {
                    throw new InvalidArgumentException('Select a reservation to pay its security deposit.');
                }
                $stmt = $pdo->prepare('SELECT * FROM reservations WHERE reservation_id = ? FOR UPDATE');
                $stmt->execute([$reservationId]);
                $reservation = $stmt->fetch();
                if (!$reservation || (int) $reservation['customer_id'] !== (int) ($input['customer_id'] ?? 0)) {
                    throw new InvalidArgumentException('The selected reservation does not belong to this customer.');
                }
                if ($reservation['status'] !== 'confirmed' || trim((string) ($reservation['approval_note'] ?? '')) === ''
                    || empty($reservation['approver_signature']) || empty($reservation['customer_signature'])) {
                    throw new InvalidArgumentException('Pay the security deposit after the signed reservation request is approved.');
                }
                $deposit = (new SecurityDepositService())->reservationSummary($reservationId);
                if ($deposit['required'] <= 0 || $deposit['requestable'] <= 0) {
                    throw new InvalidArgumentException('There is no security deposit balance available for payment on this reservation.');
                }
                if ((float) $amount > $deposit['requestable'] + 0.001) {
                    throw new InvalidArgumentException('Payment exceeds the remaining security deposit after completed and pending requests.');
                }
            }
            if ($invoiceId) {
                $stmt = $pdo->prepare('SELECT * FROM invoices WHERE invoice_id = ? FOR UPDATE');
                $stmt->execute([$invoiceId]);
                $invoice = $stmt->fetch();
                if (!$invoice) {
                    throw new InvalidArgumentException('Invoice not found.');
                }
                if ((int) $invoice['customer_id'] !== (int) ($input['customer_id'] ?? 0)) {
                    throw new InvalidArgumentException('The selected invoice does not belong to this customer.');
                }
                if ($type === 'payment' && strncmp((string) ($invoice['notes'] ?? ''), 'Booking prepayment:', 19) === 0) {
                    self::assertBookingPaymentAllowed((int) $invoice['reservation_id']);
                }
                if (in_array($invoice['status'], ['draft', 'void'], true) && $type === 'payment') {
                    throw new InvalidArgumentException('Payments can only be requested against issued invoices.');
                }
                if ($type === 'payment') {
                    $balance = Database::fetch('SELECT outstanding_balance FROM vw_invoice_balances WHERE invoice_id = ?', [$invoiceId]);
                    $pending = Database::fetch(
                        "SELECT COUNT(*) AS total FROM financial_transactions
                         WHERE invoice_id = ? AND transaction_type = 'payment' AND status = 'pending'",
                        [$invoiceId]
                    );
                    if ((int) ($pending['total'] ?? 0) > 0) {
                        throw new InvalidArgumentException('A payment request is already pending for this invoice. Check its status before trying again.');
                    }
                    $available = max(0, (float) ($balance['outstanding_balance'] ?? 0));
                    if ((float) $amount > $available + 0.001) {
                        throw new InvalidArgumentException('Payment exceeds the invoice balance available after pending Mobile Money requests.');
                    }
                }
            }
            // Only eligible, authenticated payment requests reach provider configuration and ledger creation.
            HdevClient::paymentReady();
            $dup = Database::fetch('SELECT transaction_id FROM financial_transactions WHERE external_reference = ?', [$txRef]);
            if ($dup) {
                throw new RuntimeException('Could not allocate a unique payment reference.');
            }
            $number = (new ReservationService())->nextCode('TXN');
            Database::execute(
                'INSERT INTO financial_transactions (
                    transaction_number, customer_id, invoice_id, reservation_id, rental_id,
                    transaction_type, amount, payment_method, external_reference, provider_name, status, reason, processed_by, transaction_date
                 ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())',
                [
                    $number,
                    $input['customer_id'],
                    $invoiceId,
                    $reservationId,
                    $type === 'deposit_received' ? null : ($input['rental_id'] ?? null),
                    $type,
                    $amount,
                    'mobile_money',
                    $txRef,
                    'HDEV',
                    'pending',
                    $input['reason'] ?? 'HDEV MoMo request',
                    Auth::id(),
                ]
            );
            $created = Database::fetch('SELECT * FROM financial_transactions WHERE transaction_id = ?', [Database::lastId()]);
            if ($invoiceId) {
                ReservationPaymentStatusService::syncForInvoice($invoiceId);
            }
            return $created;
        });

        $cfg = HdevClient::config();
        $callback = trim($cfg['callback_url']);
        if ($callback === '') {
            $callback = $this->localCallbackFallback();
        }
        try {
            $pay = \hdev_payment::pay($tel, (int) round((float) $amount), $txRef, $callback);
        } catch (\Throwable) {
            $this->notifyTransactionStatus($row, 'pending');
            throw new InvalidArgumentException('HDEV did not return a final request response. The ledger remains pending; check its status before retrying.');
        }
        $st = HdevClient::statusOf($pay);
        $gatewayMessage = HdevClient::messageOf($pay);
        Audit::log('hdev.pay_initiated', 'financial_transaction', (string) $row['transaction_id'], $txRef . ' ' . $st);

        if (!HdevClient::accepted($pay)) {
            $detail = $gatewayMessage !== '' ? $gatewayMessage : 'Check the phone number, amount and API credentials.';
            $failureStatus = $st === 'cancelled' ? 'cancelled' : 'failed';
            Database::transaction(function () use ($detail, $row, $failureStatus): void {
                Database::execute(
                    'UPDATE financial_transactions SET status = ?, reason = CONCAT(COALESCE(reason,\'\'), ?) WHERE transaction_id = ? AND status = \'pending\'',
                    [$failureStatus, ' | gateway: ' . substr($detail, 0, 180), $row['transaction_id']]
                );
                Audit::log('hdev.pay_' . $failureStatus, 'financial_transaction', (string) $row['transaction_id'], (string) $row['external_reference'], null, ['status' => $failureStatus], 'failure');
                if (!empty($row['invoice_id'])) {
                    (new InvoiceService())->refreshPaymentStatus((int) $row['invoice_id']);
                }
                $failed = Database::fetch('SELECT * FROM financial_transactions WHERE transaction_id = ?', [$row['transaction_id']]);
                if ($failed) {
                    $this->notifyTransactionStatus($failed, 'failed');
                }
            });
            throw new InvalidArgumentException('HDEV did not accept the payment request. ' . $detail);
        }

        $verified = $this->settleByReference($txRef);
        $current = $verified ?? Database::fetch('SELECT * FROM financial_transactions WHERE transaction_id = ?', [$row['transaction_id']]);

        if (($current['status'] ?? '') === 'pending') {
            $this->notifyTransactionStatus($current, 'pending');
            (new SmsService())->sendToCustomer(
                (int) $row['customer_id'],
                'payment_prompt',
                'MoMo payment requested',
                'Approve MoMo of RWF ' . (int) $amount . ' for ' . $row['transaction_number'] . '. Dial and confirm the prompt on ' . $tel . '.',
                'financial_transaction',
                (int) $row['transaction_id']
            );
        }

        return $current;
    }

    public function settleByReference(string $txRef): ?array
    {
        $txRef = trim($txRef);
        if ($txRef === '') {
            return null;
        }

        $existing = Database::fetch('SELECT * FROM financial_transactions WHERE external_reference = ?', [$txRef]);
        if (!$existing) {
            return null;
        }
        if ($existing['status'] !== 'pending') {
            return $existing;
        }

        HdevClient::paymentReady();
        $result = \hdev_payment::get_pay($txRef);
        $st = HdevClient::statusOf($result);
        $remoteAmount = HdevClient::remoteAmount($result);
        $gatewayMessage = HdevClient::messageOf($result);

        return Database::transaction(function (PDO $pdo) use ($txRef, $st, $remoteAmount, $gatewayMessage) {
            $stmt = $pdo->prepare('SELECT * FROM financial_transactions WHERE external_reference = ? FOR UPDATE');
            $stmt->execute([$txRef]);
            $row = $stmt->fetch();
            if (!$row) {
                return null;
            }
            if ($row['status'] === 'completed') {
                return $row;
            }
            if ($row['status'] !== 'pending') {
                return $row;
            }

            if ($st === 'success') {
                if ($remoteAmount !== null && (float) $remoteAmount !== (float) $row['amount']) {
                    Audit::log('hdev.amount_mismatch', 'financial_transaction', (string) $row['transaction_id'], $txRef, null, null, 'failure');
                    throw new InvalidArgumentException('HDEV amount does not match the pending ledger row. Payment was not completed.');
                }
                Database::execute(
                    "UPDATE financial_transactions SET status = 'completed', transaction_date = NOW() WHERE transaction_id = ? AND status = 'pending'",
                    [$row['transaction_id']]
                );
                if ($row['invoice_id'] && in_array($row['transaction_type'], ['payment', 'payment_refund', 'adjustment'], true)) {
                    (new InvoiceService())->refreshPaymentStatus((int) $row['invoice_id']);
                }
                Audit::log('hdev.pay_completed', 'financial_transaction', (string) $row['transaction_id'], $txRef);
                $completed = Database::fetch('SELECT * FROM financial_transactions WHERE transaction_id = ?', [$row['transaction_id']]);
                if ($completed) {
                    $this->notifyTransactionStatus($completed, 'completed');
                }
                return $completed;
            }

            if (in_array($st, ['failed', 'cancelled'], true)) {
                $note = $gatewayMessage !== '' ? ' | gateway: ' . substr($gatewayMessage, 0, 180) : '';
                Database::execute(
                    "UPDATE financial_transactions SET status = ?, reason = CONCAT(COALESCE(reason,''), ?) WHERE transaction_id = ? AND status = 'pending'",
                    [$st, $note, $row['transaction_id']]
                );
                Audit::log('hdev.pay_' . $st, 'financial_transaction', (string) $row['transaction_id'], $txRef, null, ['status' => $st], 'failure');
                if ($row['invoice_id']) {
                    (new InvoiceService())->refreshPaymentStatus((int) $row['invoice_id']);
                }
                $updated = Database::fetch('SELECT * FROM financial_transactions WHERE transaction_id = ?', [$row['transaction_id']]);
                if ($updated) {
                    $this->notifyTransactionStatus($updated, $st);
                }
                return $updated;
            }

            return Database::fetch('SELECT * FROM financial_transactions WHERE transaction_id = ?', [$row['transaction_id']]);
        });
    }

    public function notifyIfCompleted(array $before, ?array $after): void
    {
        if (!$after || ($after['status'] ?? '') !== 'completed') {
            return;
        }
        if (($before['status'] ?? '') === 'completed') {
            return;
        }
        (new SmsService())->sendToCustomer(
            (int) $after['customer_id'],
            'payment_received',
            'Payment received',
            'Payment of RWF ' . (int) $after['amount'] . ' was confirmed. Ref ' . $after['external_reference'] . '.',
            'financial_transaction',
            (int) $after['transaction_id']
        );
    }

    private function notifyTransactionStatus(array $row, string $status): void
    {
        $labels = [
            'pending' => ['payment_pending', 'Payment pending', 'The Mobile Money request is waiting for provider confirmation.'],
            'completed' => ['payment_successful', 'Payment successful', 'Your payment of ' . Money::format($row['amount']) . ' was verified by HDEV and recorded.'],
            'failed' => ['payment_failed', 'Payment failed', 'HDEV reported that the payment request failed. No payment was recorded.'],
            'cancelled' => ['payment_cancelled', 'Payment cancelled', 'The Mobile Money request was cancelled. No payment was recorded.'],
        ];
        if (!isset($labels[$status])) {
            return;
        }
        [$type, $subject, $message] = $labels[$status];
        $message .= ' Transaction ' . $row['transaction_number'] . ' (' . Money::format($row['amount']) . ').';
        $notifications = new NotificationService();
        $notifications->notifyUser(null, (int) $row['customer_id'], $type, $subject, $message, 'financial_transaction', (int) $row['transaction_id']);
        $notifications->notifyUsersWithAnyPermission(
            ['transactions.view', 'transactions.manage', 'reservations.view'],
            $type,
            $subject . ': ' . $row['transaction_number'],
            $message,
            'financial_transaction',
            (int) $row['transaction_id']
        );
        if ($status === 'failed') {
            $notifications->notifyRole(
                'System Administrator',
                'payment_issue',
                'Payment issue: ' . $row['transaction_number'],
                $message,
                'financial_transaction',
                (int) $row['transaction_id']
            );
        }
    }

    private function localCallbackFallback(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = rtrim(crms_base_url(), '/');
        $scheme = $https ? 'https' : 'http';
        return $scheme . '://' . $host . $base . '/hdev_callback.php';
    }
}
