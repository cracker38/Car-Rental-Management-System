<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Audit;
use Crms\Auth;
use Crms\Database;
use Crms\Money;
use Crms\Phone;
use InvalidArgumentException;
use PDO;
use RuntimeException;

final class HdevPaymentService
{
    public function initiate(array $input): array
    {
        $type = $input['transaction_type'] ?? 'payment';
        if (!in_array($type, ['payment', 'deposit_received'], true)) {
            throw new InvalidArgumentException('Mobile Money can only initiate a payment or deposit receipt.');
        }
        $amount = Money::round($input['amount'] ?? 0);
        if ((float) $amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }
        $tel = Phone::hdevTel((string) ($input['phone'] ?? ''));
        if ($tel === null) {
            throw new InvalidArgumentException('Enter a valid Rwanda mobile number for MoMo (07xxxxxxxx).');
        }

        // Validate local configuration before creating a pending ledger row.
        HdevClient::paymentReady();

        $txRef = 'CRMS-' . strtoupper(bin2hex(random_bytes(6)));
        $row = Database::transaction(function (PDO $pdo) use ($input, $type, $amount, $txRef) {
            $invoiceId = !empty($input['invoice_id']) ? (int) $input['invoice_id'] : null;
            if ($type === 'payment' && !$invoiceId) {
                throw new InvalidArgumentException('Select an issued invoice for a Mobile Money payment.');
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
                if (in_array($invoice['status'], ['draft', 'void'], true) && $type === 'payment') {
                    throw new InvalidArgumentException('Payments can only be requested against issued invoices.');
                }
                if ($type === 'payment') {
                    $balance = Database::fetch('SELECT outstanding_balance FROM vw_invoice_balances WHERE invoice_id = ?', [$invoiceId]);
                    $reserved = Database::fetch(
                        "SELECT COALESCE(SUM(amount),0) AS amount FROM financial_transactions
                         WHERE invoice_id = ? AND transaction_type = 'payment' AND payment_method = 'mobile_money' AND status = 'pending'",
                        [$invoiceId]
                    );
                    $available = max(0, (float) ($balance['outstanding_balance'] ?? 0) - (float) ($reserved['amount'] ?? 0));
                    if ((float) $amount > $available + 0.001) {
                        throw new InvalidArgumentException('Payment exceeds the invoice balance available after pending Mobile Money requests.');
                    }
                }
            }
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
                    $input['reservation_id'] ?? null,
                    $input['rental_id'] ?? null,
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
            return Database::fetch('SELECT * FROM financial_transactions WHERE transaction_id = ?', [Database::lastId()]);
        });

        $cfg = HdevClient::config();
        $callback = trim($cfg['callback_url']);
        if ($callback === '') {
            $callback = $this->localCallbackFallback();
        }
        $pay = \hdev_payment::pay($tel, (int) round((float) $amount), $txRef, $callback);
        $st = HdevClient::statusOf($pay);
        $gatewayMessage = HdevClient::messageOf($pay);
        Audit::log('hdev.pay_initiated', 'financial_transaction', (string) $row['transaction_id'], $txRef . ' ' . $st);

        if (!HdevClient::accepted($pay)) {
            $detail = $gatewayMessage !== '' ? $gatewayMessage : 'Check the phone number, amount and API credentials.';
            Database::execute(
                "UPDATE financial_transactions SET status = 'failed', reason = CONCAT(COALESCE(reason,''), ?) WHERE transaction_id = ? AND status = 'pending'",
                [' | gateway: ' . substr($detail, 0, 180), $row['transaction_id']]
            );
            throw new InvalidArgumentException('HDEV did not accept the payment request. ' . $detail);
        }

        $verified = $this->settleByReference($txRef);
        $current = $verified ?? Database::fetch('SELECT * FROM financial_transactions WHERE transaction_id = ?', [$row['transaction_id']]);

        if (($current['status'] ?? '') === 'pending') {
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
                return $completed;
            }

            if ($st === 'failed') {
                $note = $gatewayMessage !== '' ? ' | gateway: ' . substr($gatewayMessage, 0, 180) : '';
                Database::execute(
                    "UPDATE financial_transactions SET status = 'failed', reason = CONCAT(COALESCE(reason,''), ?) WHERE transaction_id = ? AND status = 'pending'",
                    [$note, $row['transaction_id']]
                );
                Audit::log('hdev.pay_failed', 'financial_transaction', (string) $row['transaction_id'], $txRef, null, null, 'failure');
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

    private function localCallbackFallback(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = rtrim(crms_base_url(), '/');
        $scheme = $https ? 'https' : 'http';
        return $scheme . '://' . $host . $base . '/hdev_callback.php';
    }
}
