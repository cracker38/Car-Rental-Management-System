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

final class PawaPayPaymentService
{
    private const PROVIDERS = ['AIRTEL_RWA', 'MTN_MOMO_RWA'];

    public static function assertBookingPaymentAllowed(int $reservationId): void
    {
        $booking = Database::fetch(
            'SELECT r.status, r.approval_note, r.approver_signature, r.customer_signature,
                    r.customer_signed_at, r.customer_signature_user_id, c.linked_user_id AS customer_user_id
             FROM reservations r JOIN customers c ON c.customer_id = r.customer_id WHERE r.reservation_id = ?',
            [$reservationId]
        );
        if (!$booking || $booking['status'] !== 'confirmed' || trim((string) ($booking['approval_note'] ?? '')) === ''
            || empty($booking['approver_signature']) || empty($booking['customer_signature'])
            || empty($booking['customer_signed_at']) || empty($booking['customer_signature_user_id'])
            || (int) $booking['customer_signature_user_id'] !== (int) ($booking['customer_user_id'] ?? 0)) {
            throw new InvalidArgumentException('Payment is available only after the signed reservation request has a signed approval decision.');
        }
    }

    /** @return array<string,mixed> */
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
            throw new InvalidArgumentException('PawaPay Rwanda payments must be a whole RWF amount.');
        }
        $phoneNumber = Phone::rwandaMobile((string) ($input['phone'] ?? ''));
        if ($phoneNumber === null) {
            throw new InvalidArgumentException('Enter a valid Rwanda mobile number (07xxxxxxxx).');
        }
        $provider = strtoupper(trim((string) ($input['provider'] ?? '')));
        if (!in_array($provider, self::PROVIDERS, true)) {
            throw new InvalidArgumentException('Choose whether this wallet uses MTN or Airtel Money.');
        }

        $depositId = self::uuidV4();
        $row = Database::transaction(function (PDO $pdo) use ($input, $type, $amount, $depositId) {
            $invoiceId = !empty($input['invoice_id']) ? (int) $input['invoice_id'] : null;
            $reservationId = !empty($input['reservation_id']) ? (int) $input['reservation_id'] : null;
            if ($type === 'payment' && !$invoiceId) {
                throw new InvalidArgumentException('Select an issued invoice for a Mobile Money payment.');
            }
            if ($type === 'deposit_received') {
                if ($invoiceId || !$reservationId) {
                    throw new InvalidArgumentException('Select a reservation to pay its security deposit.');
                }
                $stmt = $pdo->prepare(
                    'SELECT r.*, c.linked_user_id AS customer_user_id FROM reservations r
                     JOIN customers c ON c.customer_id = r.customer_id WHERE r.reservation_id = ? FOR UPDATE'
                );
                $stmt->execute([$reservationId]);
                $reservation = $stmt->fetch();
                if (!$reservation || (int) $reservation['customer_id'] !== (int) ($input['customer_id'] ?? 0)) {
                    throw new InvalidArgumentException('The selected reservation does not belong to this customer.');
                }
                if ($reservation['status'] !== 'confirmed' || trim((string) ($reservation['approval_note'] ?? '')) === ''
                    || empty($reservation['approver_signature']) || empty($reservation['customer_signature'])
                    || empty($reservation['customer_signed_at']) || empty($reservation['customer_signature_user_id'])
                    || (int) $reservation['customer_signature_user_id'] !== (int) ($reservation['customer_user_id'] ?? 0)) {
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
                    $balance = Database::fetch('SELECT outstanding_balance FROM ' . InvoiceBalanceSql::table('b') . ' WHERE invoice_id = ?', [$invoiceId]);
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
            PawaPayClient::assertReady();
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
                    $depositId,
                    'PawaPay',
                    'pending',
                    $input['reason'] ?? 'PawaPay Mobile Money request',
                    Auth::id(),
                ]
            );
            $created = Database::fetch('SELECT * FROM financial_transactions WHERE transaction_id = ?', [Database::lastId()]);
            if ($invoiceId) {
                ReservationPaymentStatusService::syncForInvoice($invoiceId);
            }
            return $created;
        });

        $client = new PawaPayClient();
        $response = $client->createDeposit([
            'depositId' => $depositId,
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => $phoneNumber,
                    'provider' => $provider,
                ],
            ],
            'amount' => (string) (int) round((float) $amount),
            'currency' => 'RWF',
            'country' => 'RWA',
            'customerTimestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'clientReferenceId' => (string) $row['transaction_number'],
            'customerMessage' => 'Car rental payment',
        ]);
        $status = strtoupper(trim((string) ($response['status'] ?? '')));
        $message = PawaPayClient::failureMessage($response);

        if (!empty($response['transport_error'])) {
            Audit::log('pawapay.deposit_no_response', 'financial_transaction', (string) $row['transaction_id'], $depositId, null, ['status' => 'pending'], 'failure');
            $this->notifyTransactionStatus($row, 'pending');
            throw new InvalidArgumentException('PawaPay did not return a final response. The transaction remains pending; check its status before retrying.');
        }

        if ((int) ($response['http_status'] ?? 0) >= 500) {
            Audit::log('pawapay.deposit_server_error', 'financial_transaction', (string) $row['transaction_id'], $depositId, null, ['status' => 'pending'], 'failure');
            $this->notifyTransactionStatus($row, 'pending');
            throw new InvalidArgumentException('PawaPay returned a server error. The transaction remains pending; check its status before retrying.');
        }

        if ($status === 'REJECTED' || ((int) ($response['http_status'] ?? 0) >= 400 && $status !== 'ACCEPTED')) {
            $detail = $message !== '' ? $message : 'PawaPay rejected the deposit request.';
            $failed = Database::transaction(function () use ($detail, $row): ?array {
                Database::execute(
                    "UPDATE financial_transactions SET status = 'failed', reason = CONCAT(COALESCE(reason,''), ?)
                     WHERE transaction_id = ? AND status = 'pending'",
                    [' | PawaPay: ' . substr($detail, 0, 180), $row['transaction_id']]
                );
                Audit::log('pawapay.deposit_rejected', 'financial_transaction', (string) $row['transaction_id'], (string) $row['external_reference'], null, ['status' => 'failed'], 'failure');
                if (!empty($row['invoice_id'])) {
                    (new InvoiceService())->refreshPaymentStatus((int) $row['invoice_id']);
                }
                return Database::fetch('SELECT * FROM financial_transactions WHERE transaction_id = ?', [$row['transaction_id']]);
            });
            if ($failed) {
                $this->notifyTransactionStatus($failed, 'failed');
            }
            throw new InvalidArgumentException('PawaPay rejected the payment request. ' . $detail);
        }

        if (!in_array($status, ['ACCEPTED', 'DUPLICATE_IGNORED'], true)) {
            $httpStatus = (int) ($response['http_status'] ?? 0);
            if ($httpStatus >= 200 && $httpStatus < 300) {
                // A successful HTTP response with an unknown provider state is ambiguous; keep it pending.
                Audit::log('pawapay.deposit_unrecognized_response', 'financial_transaction', (string) $row['transaction_id'], $depositId, null, ['provider_status' => $status], 'failure');
                $this->notifyTransactionStatus($row, 'pending');
                throw new InvalidArgumentException('PawaPay returned an unfamiliar response. The transaction remains pending; check its status before retrying.');
            }
            $detail = $message !== '' ? $message : 'PawaPay returned an invalid response.';
            throw new InvalidArgumentException('PawaPay could not confirm the request. Check the payment status before retrying. ' . $detail);
        }

        Audit::log('pawapay.deposit_initiated', 'financial_transaction', (string) $row['transaction_id'], $depositId . ' ' . $status);
        $this->notifyTransactionStatus($row, 'pending');
        (new SmsService())->sendToCustomer(
            (int) $row['customer_id'],
            'payment_prompt',
            'Payment requested',
            'Approve the PawaPay Mobile Money prompt for RWF ' . (int) $amount . ' on ' . $phoneNumber . '.',
            'financial_transaction',
            (int) $row['transaction_id']
        );
        return $row;
    }

    /** @return array<string,mixed>|null */
    public function settleByReference(string $depositId): ?array
    {
        $depositId = trim($depositId);
        if ($depositId === '') {
            return null;
        }
        $existing = Database::fetch('SELECT * FROM financial_transactions WHERE external_reference = ?', [$depositId]);
        if (!$existing || $existing['status'] !== 'pending') {
            return $existing;
        }

        $result = (new PawaPayClient())->getDeposit($depositId);
        if (!empty($result['transport_error'])) {
            throw new InvalidArgumentException('Could not check PawaPay status right now. The transaction remains pending.');
        }
        $data = is_array($result['data'] ?? null) ? $result['data'] : $result;
        $lookupStatus = strtoupper(trim((string) ($result['status'] ?? '')));
        if ($lookupStatus === 'NOT_FOUND') {
            return $existing;
        }
        if ($lookupStatus !== '' && $lookupStatus !== 'FOUND') {
            $message = PawaPayClient::failureMessage($result);
            throw new InvalidArgumentException($message !== '' ? $message : 'PawaPay could not find this deposit yet. The transaction remains pending.');
        }

        $providerStatus = strtoupper(trim((string) ($data['status'] ?? '')));
        $amount = $data['depositedAmount'] ?? $data['amount'] ?? $data['requestedAmount'] ?? null;
        $failureMessage = PawaPayClient::failureMessage($data);
        if ($providerStatus === 'COMPLETED' && $amount !== null
            && (float) Money::round($amount) !== (float) Money::round($existing['amount'])) {
            Audit::log('pawapay.amount_mismatch', 'financial_transaction', (string) $existing['transaction_id'], $depositId, null, null, 'failure');
            throw new InvalidArgumentException('PawaPay amount does not match the pending ledger row. Payment was not completed.');
        }

        return Database::transaction(function (PDO $pdo) use ($depositId, $providerStatus, $failureMessage) {
            $stmt = $pdo->prepare('SELECT * FROM financial_transactions WHERE external_reference = ? FOR UPDATE');
            $stmt->execute([$depositId]);
            $row = $stmt->fetch();
            if (!$row || $row['status'] === 'completed' || $row['status'] !== 'pending') {
                return $row ?: null;
            }
            if ($providerStatus === 'COMPLETED') {
                Database::execute(
                    "UPDATE financial_transactions SET status = 'completed', transaction_date = NOW() WHERE transaction_id = ? AND status = 'pending'",
                    [$row['transaction_id']]
                );
                if ($row['invoice_id'] && in_array($row['transaction_type'], ['payment', 'payment_refund', 'adjustment'], true)) {
                    (new InvoiceService())->refreshPaymentStatus((int) $row['invoice_id']);
                }
                Audit::log('pawapay.deposit_completed', 'financial_transaction', (string) $row['transaction_id'], $depositId);
                $completed = Database::fetch('SELECT * FROM financial_transactions WHERE transaction_id = ?', [$row['transaction_id']]);
                if ($completed) {
                    $this->notifyTransactionStatus($completed, 'completed');
                }
                return $completed;
            }
            if ($providerStatus === 'FAILED') {
                $note = $failureMessage !== '' ? ' | PawaPay: ' . substr($failureMessage, 0, 180) : '';
                Database::execute(
                    "UPDATE financial_transactions SET status = 'failed', reason = CONCAT(COALESCE(reason,''), ?)
                     WHERE transaction_id = ? AND status = 'pending'",
                    [$note, $row['transaction_id']]
                );
                Audit::log('pawapay.deposit_failed', 'financial_transaction', (string) $row['transaction_id'], $depositId, null, ['status' => 'failed'], 'failure');
                if ($row['invoice_id']) {
                    (new InvoiceService())->refreshPaymentStatus((int) $row['invoice_id']);
                }
                $updated = Database::fetch('SELECT * FROM financial_transactions WHERE transaction_id = ?', [$row['transaction_id']]);
                if ($updated) {
                    $this->notifyTransactionStatus($updated, 'failed');
                }
                return $updated;
            }
            return Database::fetch('SELECT * FROM financial_transactions WHERE transaction_id = ?', [$row['transaction_id']]);
        });
    }

    public function notifyIfCompleted(array $before, ?array $after): void
    {
        if (!$after || ($after['status'] ?? '') !== 'completed' || ($before['status'] ?? '') === 'completed') {
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
            'completed' => ['payment_successful', 'Payment successful', 'Your payment of ' . Money::format($row['amount']) . ' was verified by PawaPay and recorded.'],
            'failed' => ['payment_failed', 'Payment failed', 'PawaPay reported that the payment request failed. No payment was recorded.'],
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

    private static function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4)
            . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20, 12);
    }
}
