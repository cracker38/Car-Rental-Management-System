<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Audit;
use Crms\Auth;
use Crms\Database;
use Crms\Money;
use InvalidArgumentException;
use PDO;

/**
 * Immutable ledger. Corrections are reversals/adjustments, never edits/deletes.
 * Amounts are always positive; transaction_type defines direction.
 */
final class FinanceService
{
    public function post(array $input): array
    {
        $type = $input['transaction_type'];
        $allowed = ['payment', 'payment_refund', 'deposit_received', 'deposit_deduction', 'deposit_refund', 'adjustment'];
        if (!in_array($type, $allowed, true)) {
            throw new InvalidArgumentException('Invalid transaction type.');
        }
        $amount = Money::round($input['amount'] ?? 0);
        if ((float) $amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        return Database::transaction(function (PDO $pdo) use ($input, $type, $amount) {
            $invoiceId = !empty($input['invoice_id']) ? (int) $input['invoice_id'] : null;
            $relatedId = !empty($input['related_transaction_id']) ? (int) $input['related_transaction_id'] : null;
            $ref = trim((string) ($input['external_reference'] ?? ''));
            if ($ref !== '') {
                $dup = Database::fetch('SELECT transaction_id FROM financial_transactions WHERE external_reference = ? FOR UPDATE', [$ref]);
                if ($dup) {
                    throw new InvalidArgumentException('This payment reference was already processed.');
                }
            }

            $invoice = null;
            if ($invoiceId) {
                $stmt = $pdo->prepare('SELECT * FROM invoices WHERE invoice_id = ? FOR UPDATE');
                $stmt->execute([$invoiceId]);
                $invoice = $stmt->fetch();
                if (!$invoice) {
                    throw new InvalidArgumentException('Invoice not found.');
                }
                if (in_array($invoice['status'], ['draft', 'void'], true) && $type === 'payment') {
                    throw new InvalidArgumentException('Payments can only be posted against issued invoices.');
                }
            }

            if ($type === 'payment_refund') {
                if (!$relatedId) {
                    throw new InvalidArgumentException('Refunds must link to the original payment.');
                }
                $orig = $this->lockTxn($pdo, $relatedId);
                if ($orig['transaction_type'] !== 'payment' || $orig['status'] !== 'completed') {
                    throw new InvalidArgumentException('Refunds must target a completed payment.');
                }
                $remaining = $this->refundableRemaining((int) $orig['transaction_id'], (float) $orig['amount']);
                if ((float) $amount > $remaining + 0.001) {
                    throw new InvalidArgumentException('Refund exceeds the remaining refundable amount.');
                }
            }

            if (in_array($type, ['deposit_deduction', 'deposit_refund'], true)) {
                $customerId = (int) $input['customer_id'];
                $stmt = $pdo->prepare('SELECT customer_id FROM customers WHERE customer_id = ? FOR UPDATE');
                $stmt->execute([$customerId]);
                $bal = $this->depositBalance($customerId, $input['rental_id'] ?? null);
                if ((float) $amount > $bal + 0.001) {
                    throw new InvalidArgumentException('Amount exceeds the remaining deposit balance.');
                }
            }

            $number = (new ReservationService())->nextCode('TXN');
            Database::execute(
                'INSERT INTO financial_transactions (
                    transaction_number, customer_id, invoice_id, reservation_id, rental_id, related_transaction_id,
                    transaction_type, amount, payment_method, external_reference, provider_name, status, reason, processed_by, transaction_date
                 ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())',
                [
                    $number,
                    $input['customer_id'],
                    $invoiceId,
                    $input['reservation_id'] ?? null,
                    $input['rental_id'] ?? null,
                    $relatedId,
                    $type,
                    $amount,
                    $input['payment_method'] ?? null,
                    $ref !== '' ? $ref : null,
                    $input['provider_name'] ?? null,
                    'completed',
                    $input['reason'] ?? null,
                    Auth::id(),
                ]
            );
            $id = (int) Database::lastId();
            if ($invoiceId && in_array($type, ['payment', 'payment_refund', 'adjustment'], true)) {
                (new InvoiceService())->refreshPaymentStatus($invoiceId);
            }
            Audit::log('finance.posted', 'financial_transaction', (string) $id, $number . ' ' . $type . ' ' . $amount);
            return Database::fetch('SELECT * FROM financial_transactions WHERE transaction_id = ?', [$id]);
        });
    }

    public function refundableRemaining(int $paymentId, float $originalAmount): float
    {
        $row = Database::fetch(
            "SELECT COALESCE(SUM(amount),0) AS refunded
             FROM financial_transactions
             WHERE related_transaction_id = ? AND transaction_type = 'payment_refund' AND status = 'completed'",
            [$paymentId]
        );
        return (float) Money::sub($originalAmount, $row['refunded'] ?? 0);
    }

    public function depositBalance(int $customerId, mixed $rentalId = null): float
    {
        $sql = "SELECT COALESCE(SUM(CASE
                    WHEN transaction_type = 'deposit_received' AND status = 'completed' THEN amount
                    WHEN transaction_type IN ('deposit_deduction','deposit_refund') AND status = 'completed' THEN -amount
                    ELSE 0 END),0) AS bal
                FROM financial_transactions WHERE customer_id = ?";
        $params = [$customerId];
        if ($rentalId) {
            $sql .= ' AND rental_id = ?';
            $params[] = $rentalId;
        }
        $row = Database::fetch($sql, $params);
        return (float) ($row['bal'] ?? 0);
    }

    private function lockTxn(PDO $pdo, int $id): array
    {
        $stmt = $pdo->prepare('SELECT * FROM financial_transactions WHERE transaction_id = ? FOR UPDATE');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new InvalidArgumentException('Original transaction not found.');
        }
        return $row;
    }
}
