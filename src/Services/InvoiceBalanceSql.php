<?php
declare(strict_types=1);

namespace Crms\Services;

use InvalidArgumentException;

final class InvoiceBalanceSql
{
    public static function table(string $alias): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $alias) !== 1) {
            throw new InvalidArgumentException('Invalid invoice balance SQL alias.');
        }

        $netPaid = "CASE WHEN ft.transaction_type = 'payment' AND ft.status = 'completed' THEN ft.amount
            WHEN ft.transaction_type = 'payment_refund' AND ft.status = 'completed' THEN -ft.amount
            WHEN ft.transaction_type = 'adjustment' AND ft.status = 'completed' AND ft.adjustment_direction = 'credit' THEN ft.amount
            WHEN ft.transaction_type = 'adjustment' AND ft.status = 'completed' AND ft.adjustment_direction = 'debit' THEN -ft.amount
            ELSE 0 END";

        return "(SELECT i.invoice_id, i.invoice_number, i.customer_id, i.total_amount,
                    COALESCE(SUM({$netPaid}), 0) AS net_paid,
                    GREATEST(i.total_amount - COALESCE(SUM({$netPaid}), 0), 0) AS outstanding_balance,
                    i.status
                FROM invoices i
                LEFT JOIN financial_transactions ft ON ft.invoice_id = i.invoice_id
                GROUP BY i.invoice_id, i.invoice_number, i.customer_id, i.total_amount, i.status) AS `{$alias}`";
    }
}
