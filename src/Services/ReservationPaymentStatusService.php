<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Database;

final class ReservationPaymentStatusService
{
    public static function syncForInvoice(int $invoiceId): void
    {
        $invoice = Database::fetch(
            "SELECT i.invoice_id, i.reservation_id, i.notes, i.status, r.status AS reservation_status
             FROM invoices i LEFT JOIN reservations r ON r.reservation_id = i.reservation_id
             WHERE i.invoice_id = ?",
            [$invoiceId]
        );
        if (!$invoice || empty($invoice['reservation_id']) || strncmp((string) ($invoice['notes'] ?? ''), 'Booking prepayment:', 19) !== 0) {
            return;
        }

        $balance = Database::fetch('SELECT outstanding_balance, net_paid FROM ' . InvoiceBalanceSql::table('b') . ' WHERE invoice_id = ?', [$invoiceId]);
        $activity = Database::fetch(
            "SELECT
                COALESCE(SUM(CASE WHEN transaction_type = 'payment' AND status = 'completed' THEN amount
                                  WHEN transaction_type = 'payment_refund' AND status = 'completed' THEN -amount ELSE 0 END), 0) AS net_paid,
                COALESCE(SUM(CASE WHEN transaction_type = 'payment_refund' AND status = 'completed' THEN amount ELSE 0 END), 0) AS refunded,
                COALESCE(SUM(CASE WHEN transaction_type = 'payment' AND status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_count,
                COALESCE(SUM(CASE WHEN transaction_type = 'payment' AND status = 'failed' THEN 1 ELSE 0 END), 0) AS failed_count,
                COALESCE(SUM(CASE WHEN transaction_type = 'payment' AND status = 'cancelled' THEN 1 ELSE 0 END), 0) AS cancelled_count
             FROM financial_transactions WHERE invoice_id = ?",
            [$invoiceId]
        );
        $netPaid = (float) ($balance['net_paid'] ?? $activity['net_paid'] ?? 0);
        $outstanding = (float) ($balance['outstanding_balance'] ?? 0);
        $refunded = (float) ($activity['refunded'] ?? 0);

        if ($refunded > 0 && $netPaid <= 0.001) {
            $status = 'refunded';
        } elseif ($outstanding <= 0.001 && ($netPaid > 0.001 || $invoice['status'] === 'paid')) {
            $status = 'successful';
        } elseif ((int) ($activity['pending_count'] ?? 0) > 0) {
            $status = 'pending';
        } elseif (in_array($invoice['reservation_status'], ['rejected', 'cancelled', 'no_show'], true) && $netPaid <= 0.001) {
            $status = 'cancelled';
        } elseif ((int) ($activity['failed_count'] ?? 0) > 0 && $netPaid <= 0.001) {
            $status = 'failed';
        } elseif ((int) ($activity['cancelled_count'] ?? 0) > 0 && $netPaid <= 0.001) {
            $status = 'cancelled';
        } else {
            $status = 'unpaid';
        }

        Database::execute('UPDATE reservations SET payment_status = ? WHERE reservation_id = ?', [$status, $invoice['reservation_id']]);
    }
}
