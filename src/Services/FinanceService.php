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
 * Amounts are always positive; transaction_type and adjustment_direction define direction.
 */
final class FinanceService
{
    public function post(array $input): array
    {
        $type = (string) ($input['transaction_type'] ?? '');
        $allowed = ['payment', 'payment_refund', 'deposit_received', 'deposit_deduction', 'deposit_refund', 'adjustment'];
        if (!in_array($type, $allowed, true)) {
            throw new InvalidArgumentException('Invalid transaction type.');
        }
        $amount = Money::round($input['amount'] ?? 0);
        if ((float) $amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }
        if (in_array($type, ['payment_refund', 'deposit_refund', 'deposit_deduction', 'adjustment'], true)
            && trim((string) ($input['reason'] ?? '')) === '') {
            throw new InvalidArgumentException('Refunds, deductions and adjustments need a reason so they can be audited.');
        }
        if (strlen(trim((string) ($input['reason'] ?? ''))) > 500) {
            throw new InvalidArgumentException('Keep the transaction reason to 500 characters or fewer.');
        }
        if (strlen(trim((string) ($input['external_reference'] ?? ''))) > 150) {
            throw new InvalidArgumentException('Keep the transaction reference to 150 characters or fewer.');
        }
        if ((int) ($input['customer_id'] ?? 0) <= 0) {
            throw new InvalidArgumentException('Choose a customer for this transaction.');
        }
        if ($type === 'adjustment' && !in_array(($input['adjustment_direction'] ?? ''), ['credit', 'debit'], true)) {
            throw new InvalidArgumentException('Choose whether the invoice adjustment is a credit or debit.');
        }
        if (empty($input['payment_method']) || !in_array($input['payment_method'], ['cash','bank_transfer','card','mobile_money','online','other'], true)) {
            throw new InvalidArgumentException('Choose a valid payment method.');
        }
        if (in_array($input['payment_method'], ['bank_transfer', 'card', 'mobile_money', 'online'], true)
            && trim((string) ($input['external_reference'] ?? '')) === '') {
            throw new InvalidArgumentException('Enter the bank, card or provider reference for this payment method.');
        }
        $requiresApproval = in_array($type, ['payment_refund', 'deposit_deduction', 'deposit_refund', 'adjustment'], true);

        return Database::transaction(function (PDO $pdo) use ($input, $type, $amount, $requiresApproval) {
            $invoiceId = !empty($input['invoice_id']) ? (int) $input['invoice_id'] : null;
            $relatedId = !empty($input['related_transaction_id']) ? (int) $input['related_transaction_id'] : null;
            $customerId = (int) $input['customer_id'];
            $ref = trim((string) ($input['external_reference'] ?? ''));
            if ($type === 'payment' && !$invoiceId) {
                throw new InvalidArgumentException('Link payments to an issued invoice so its outstanding balance stays accurate.');
            }
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
                if ((int) $invoice['customer_id'] !== $customerId) {
                    throw new InvalidArgumentException('The selected invoice does not belong to this customer.');
                }
                if ($type === 'payment' && strncmp((string) ($invoice['notes'] ?? ''), 'Booking prepayment:', 19) === 0) {
                    $booking = Database::fetch('SELECT status FROM reservations WHERE reservation_id = ?', [(int) $invoice['reservation_id']]);
                    if (!$booking || $booking['status'] !== 'confirmed') {
                        throw new InvalidArgumentException('The booking invoice can be paid after the reservation is confirmed.');
                    }
                }
                if (in_array($invoice['status'], ['draft', 'void'], true) && in_array($type, ['payment', 'adjustment'], true)) {
                    throw new InvalidArgumentException('Payments and invoice adjustments require an issued invoice.');
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
            if ($type === 'adjustment' && !$invoiceId) {
                throw new InvalidArgumentException('Invoice adjustments must be linked to an invoice.');
            }
            if ($type === 'deposit_received' && !empty($input['reservation_id'])) {
                $booking = Database::fetch('SELECT status FROM reservations WHERE reservation_id = ?', [(int) $input['reservation_id']]);
                if (!$booking || $booking['status'] !== 'confirmed') {
                    throw new InvalidArgumentException('A reservation security deposit can be recorded after the reservation is confirmed.');
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
                if ((int) $orig['customer_id'] !== $customerId) {
                    throw new InvalidArgumentException('The original payment belongs to a different customer.');
                }
                if (!$invoiceId && !empty($orig['invoice_id'])) {
                    $invoiceId = (int) $orig['invoice_id'];
                    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE invoice_id = ? FOR UPDATE');
                    $stmt->execute([$invoiceId]);
                    $invoice = $stmt->fetch();
                    if (!$invoice || (int) $invoice['customer_id'] !== $customerId) {
                        throw new InvalidArgumentException('The original payment invoice could not be verified.');
                    }
                } elseif ($invoiceId && (int) ($orig['invoice_id'] ?? 0) !== $invoiceId) {
                    throw new InvalidArgumentException('The refund invoice must match the original payment.');
                }
                $remaining = $this->refundableRemaining((int) $orig['transaction_id'], (float) $orig['amount']);
                if ((float) $amount > $remaining + 0.001) {
                    throw new InvalidArgumentException('Refund exceeds the remaining refundable amount.');
                }
            }

            if (in_array($type, ['deposit_deduction', 'deposit_refund'], true)) {
                $stmt = $pdo->prepare('SELECT customer_id FROM customers WHERE customer_id = ? FOR UPDATE');
                $stmt->execute([$customerId]);
                if (!$stmt->fetch()) {
                    throw new InvalidArgumentException('Customer not found.');
                }
                $bal = $this->depositBalance($customerId, $input['rental_id'] ?? null);
                if ((float) $amount > $bal + 0.001) {
                    throw new InvalidArgumentException('Amount exceeds the remaining deposit balance.');
                }
            }

            $number = (new ReservationService())->nextCode('TXN');
            Database::execute(
                'INSERT INTO financial_transactions (
                    transaction_number, customer_id, invoice_id, reservation_id, rental_id, related_transaction_id,
                    transaction_type, adjustment_direction, amount, payment_method, external_reference, provider_name, status, reason,
                    processed_by, approval_requested_by, transaction_date
                 ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())',
                [
                    $number,
                    $customerId,
                    $invoiceId,
                    $input['reservation_id'] ?? null,
                    $input['rental_id'] ?? null,
                    $relatedId,
                    $type,
                    $type === 'adjustment' ? $input['adjustment_direction'] : null,
                    $amount,
                    $input['payment_method'] ?? null,
                    $ref !== '' ? $ref : null,
                    $input['provider_name'] ?? null,
                    $requiresApproval ? 'awaiting_approval' : 'completed',
                    $input['reason'] ?? null,
                    $requiresApproval ? null : Auth::id(),
                    $requiresApproval ? Auth::id() : null,
                ]
            );
            $id = (int) Database::lastId();
            if (!$requiresApproval && $invoiceId && in_array($type, ['payment', 'payment_refund', 'adjustment'], true)) {
                (new InvoiceService())->refreshPaymentStatus($invoiceId);
            }
            Audit::log(
                $requiresApproval ? 'finance.approval_requested' : 'finance.posted',
                'financial_transaction',
                (string) $id,
                $number . ' ' . $type . ' ' . $amount,
                null,
                ['status' => $requiresApproval ? 'awaiting_approval' : 'completed', 'reason' => $input['reason'] ?? null]
            );
            if (!$requiresApproval && in_array($type, ['payment', 'deposit_received'], true)) {
                $subject = $type === 'payment' ? 'Payment received: ' . $number : 'Security deposit recorded: ' . $number;
                $message = $type === 'payment'
                    ? 'A payment of ' . Money::format($amount) . ' was recorded for your invoice.'
                    : 'A security deposit of ' . Money::format($amount) . ' was recorded for your rental.';
                (new NotificationService())->notifyUser(
                    null,
                    $customerId,
                    'payment_received',
                    $subject,
                    $message,
                    'financial_transaction',
                    $id
                );
            } elseif ($requiresApproval) {
                (new NotificationService())->notifyUser(
                    null,
                    $customerId,
                    'payment_status',
                    'Financial request submitted for review: ' . $number,
                    'A ' . str_replace('_', ' ', $type) . ' for ' . Money::format($amount) . ' was submitted for approval.',
                    'financial_transaction',
                    $id
                );
            }
            return Database::fetch('SELECT * FROM financial_transactions WHERE transaction_id = ?', [$id]);
        });
    }

    public function reviewApproval(int $transactionId, string $decision, string $note = ''): array
    {
        if (!in_array($decision, ['approve', 'reject'], true)) {
            throw new InvalidArgumentException('Choose approve or reject.');
        }
        if ($decision === 'reject' && trim($note) === '') {
            throw new InvalidArgumentException('Add a reason when rejecting a request.');
        }
        if (strlen(trim($note)) > 500) {
            throw new InvalidArgumentException('Keep the review note to 500 characters or fewer.');
        }

        return Database::transaction(function (PDO $pdo) use ($transactionId, $decision, $note) {
            $txn = $this->lockTxn($pdo, $transactionId);
            if ($txn['status'] !== 'awaiting_approval') {
                throw new InvalidArgumentException('This financial request has already been reviewed.');
            }
            if ((int) ($txn['approval_requested_by'] ?? 0) === (int) Auth::id()) {
                throw new InvalidArgumentException('A different authorized user must review this request.');
            }

            $reviewerId = Auth::id();
            if (!$reviewerId) {
                throw new InvalidArgumentException('Sign in to review financial requests.');
            }
            if ($decision === 'approve') {
                $this->revalidateApproval($pdo, $txn);
                Database::execute(
                    "UPDATE financial_transactions
                     SET status = 'completed', processed_by = ?, approval_reviewed_by = ?, approval_reviewed_at = NOW(),
                         approval_note = ?, transaction_date = NOW()
                     WHERE transaction_id = ? AND status = 'awaiting_approval'",
                    [$reviewerId, $reviewerId, trim($note) !== '' ? trim($note) : null, $transactionId]
                );
                if (!empty($txn['invoice_id']) && in_array($txn['transaction_type'], ['payment_refund', 'adjustment'], true)) {
                    (new InvoiceService())->refreshPaymentStatus((int) $txn['invoice_id']);
                }
                Audit::log(
                    'finance.approval_approved',
                    'financial_transaction',
                    (string) $transactionId,
                    $txn['transaction_number'],
                    ['status' => 'awaiting_approval'],
                    ['status' => 'completed', 'amount' => $txn['amount'], 'requester_id' => $txn['approval_requested_by'], 'review_note' => trim($note)]
                );
            } else {
                Database::execute(
                    "UPDATE financial_transactions
                     SET status = 'rejected', approval_reviewed_by = ?, approval_reviewed_at = NOW(), approval_note = ?
                     WHERE transaction_id = ? AND status = 'awaiting_approval'",
                    [$reviewerId, trim($note), $transactionId]
                );
                Audit::log(
                    'finance.approval_rejected',
                    'financial_transaction',
                    (string) $transactionId,
                    trim($note),
                    ['status' => 'awaiting_approval'],
                    ['status' => 'rejected', 'amount' => $txn['amount'], 'requester_id' => $txn['approval_requested_by'], 'review_note' => trim($note)]
                );
            }
            $approved = $decision === 'approve';
            (new NotificationService())->notifyUser(
                null,
                (int) $txn['customer_id'],
                'payment_status',
                'Financial request ' . ($approved ? 'approved' : 'rejected') . ': ' . $txn['transaction_number'],
                'Your ' . str_replace('_', ' ', $txn['transaction_type']) . ' request for ' . Money::format($txn['amount'])
                    . ' was ' . ($approved ? 'approved.' : 'rejected.')
                    . (trim($note) !== '' ? ' Review note: ' . trim($note) : ''),
                'financial_transaction',
                $transactionId
            );
            return Database::fetch('SELECT * FROM financial_transactions WHERE transaction_id = ?', [$transactionId]);
        });
    }

    public function reconcile(int $transactionId, string $note): array
    {
        $note = trim($note);
        if ($note === '') {
            throw new InvalidArgumentException('Add a bank statement, receipt or reconciliation note before marking this entry reconciled.');
        }
        if (strlen($note) > 500) {
            throw new InvalidArgumentException('Keep the reconciliation note to 500 characters or fewer.');
        }

        return Database::transaction(function (PDO $pdo) use ($transactionId, $note) {
            $txn = $this->lockTxn($pdo, $transactionId);
            if ($txn['status'] !== 'completed') {
                throw new InvalidArgumentException('Only completed transactions can be reconciled.');
            }
            if (!empty($txn['reconciled_at'])) {
                throw new InvalidArgumentException('This ledger entry has already been reconciled.');
            }
            $userId = Auth::id();
            if (!$userId) {
                throw new InvalidArgumentException('Sign in to reconcile ledger entries.');
            }
            Database::execute(
                'UPDATE financial_transactions SET reconciled_at = NOW(), reconciled_by = ?, reconciliation_note = ? WHERE transaction_id = ? AND reconciled_at IS NULL',
                [$userId, $note, $transactionId]
            );
            Audit::log(
                'finance.reconciled',
                'financial_transaction',
                (string) $transactionId,
                $txn['transaction_number'],
                ['reconciled_at' => null],
                ['reconciled_by' => $userId, 'reconciliation_note' => $note]
            );
            return Database::fetch('SELECT * FROM financial_transactions WHERE transaction_id = ?', [$transactionId]);
        });
    }

    private function revalidateApproval(PDO $pdo, array $txn): void
    {
        if ($txn['transaction_type'] === 'payment_refund') {
            $original = $this->lockTxn($pdo, (int) $txn['related_transaction_id']);
            if ($original['transaction_type'] !== 'payment' || $original['status'] !== 'completed') {
                throw new InvalidArgumentException('The original payment is no longer eligible for a refund.');
            }
            if ((float) $txn['amount'] > $this->refundableRemaining((int) $original['transaction_id'], (float) $original['amount']) + 0.001) {
                throw new InvalidArgumentException('The remaining refundable amount changed. Review the request before approving.');
            }
        } elseif (in_array($txn['transaction_type'], ['deposit_deduction', 'deposit_refund'], true)) {
            $customerId = (int) $txn['customer_id'];
            $stmt = $pdo->prepare('SELECT customer_id FROM customers WHERE customer_id = ? FOR UPDATE');
            $stmt->execute([$customerId]);
            if (!$stmt->fetch() || (float) $txn['amount'] > $this->depositBalance($customerId, $txn['rental_id']) + 0.001) {
                throw new InvalidArgumentException('The remaining deposit balance changed. Review the request before approving.');
            }
        } elseif ($txn['transaction_type'] === 'adjustment') {
            $stmt = $pdo->prepare('SELECT status FROM invoices WHERE invoice_id = ? FOR UPDATE');
            $stmt->execute([(int) $txn['invoice_id']]);
            $invoice = $stmt->fetch();
            if (!$invoice || in_array($invoice['status'], ['draft', 'void'], true)) {
                throw new InvalidArgumentException('The invoice is no longer eligible for an adjustment.');
            }
        } else {
            throw new InvalidArgumentException('This transaction type does not use the approval workflow.');
        }
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
