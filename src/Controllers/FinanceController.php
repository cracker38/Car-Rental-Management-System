<?php
declare(strict_types=1);

namespace Crms\Controllers;

use Crms\Database;
use Crms\Http;
use Crms\Rbac;
use Crms\View;
use Crms\Services\FinanceService;
use Crms\Services\InvoiceBalanceSql;
use InvalidArgumentException;

final class FinanceController
{
    public function index(): void
    {
        Rbac::require('transactions.view');
        $rows = Database::fetchAll(
            'SELECT t.*, c.first_name, c.last_name, u.first_name AS reconciler_first_name, u.last_name AS reconciler_last_name,
                    reviewer.first_name AS approval_reviewer_first_name, reviewer.last_name AS approval_reviewer_last_name
             FROM financial_transactions t
             JOIN customers c ON c.customer_id = t.customer_id
             LEFT JOIN users u ON u.user_id = t.reconciled_by
             LEFT JOIN users reviewer ON reviewer.user_id = t.approval_reviewed_by
             ORDER BY t.transaction_date DESC LIMIT 300'
        );
        View::render('finance/index', ['title' => 'Ledger', 'rows' => $rows]);
    }

    public function approvals(): void
    {
        Rbac::require('transactions.approve');
        $rows = Database::fetchAll(
            "SELECT t.*, c.first_name, c.last_name, u.first_name AS requester_first_name, u.last_name AS requester_last_name,
                    i.invoice_number
             FROM financial_transactions t
             JOIN customers c ON c.customer_id = t.customer_id
             LEFT JOIN users u ON u.user_id = t.approval_requested_by
             LEFT JOIN invoices i ON i.invoice_id = t.invoice_id
             WHERE t.status = 'awaiting_approval'
             ORDER BY t.created_at ASC"
        );
        View::render('finance/approvals', ['title' => 'Financial approvals', 'rows' => $rows]);
    }

    public function review(): void
    {
        Rbac::require('transactions.approve');
        if (!Http::isPost()) {
            Http::redirect('finance/approvals');
        }
        try {
            (new FinanceService())->reviewApproval(Http::int('transaction_id'), Http::str('decision'), Http::str('approval_note'));
            Http::flash('success', Http::str('decision') === 'approve' ? 'Request approved and posted to the ledger.' : 'Request rejected and recorded in the audit log.');
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
        }
        Http::redirect('finance/approvals');
    }

    public function reconcile(): void
    {
        Rbac::require('transactions.manage');
        if (!Http::isPost()) {
            Http::redirect('finance');
        }
        try {
            (new FinanceService())->reconcile(Http::int('transaction_id'), Http::str('reconciliation_note'));
            Http::flash('success', 'Ledger entry reconciled and recorded in the audit log.');
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
        }
        Http::redirect('finance');
    }

    public function form(): void
    {
        Rbac::require('transactions.manage');
        $invoice = null;
        if (Http::int('invoice_id')) {
            $invoice = Database::fetch(
                'SELECT i.*, b.outstanding_balance FROM invoices i
                 LEFT JOIN ' . InvoiceBalanceSql::table('b') . ' ON b.invoice_id = i.invoice_id WHERE i.invoice_id = ?',
                [Http::int('invoice_id')]
            );
        }
        $customers = Database::fetchAll('SELECT customer_id, customer_code, first_name, last_name FROM customers ORDER BY last_name');
        $openInvoices = Database::fetchAll(
            "SELECT i.invoice_id, i.invoice_number, i.customer_id, c.first_name, c.last_name, b.outstanding_balance
             FROM invoices i JOIN customers c ON c.customer_id = i.customer_id
             JOIN " . InvoiceBalanceSql::table('b') . " ON b.invoice_id = i.invoice_id
             WHERE i.status IN ('issued','partially_paid','overdue','paid')
             ORDER BY i.due_date, i.invoice_id DESC LIMIT 200"
        );
        $payments = Database::fetchAll("SELECT transaction_id, transaction_number, customer_id, invoice_id, amount FROM financial_transactions WHERE transaction_type='payment' AND status='completed' ORDER BY transaction_id DESC LIMIT 50");
        if (Http::isPost()) {
            try {
                $entry = (new FinanceService())->post([
                    'customer_id' => Http::int('customer_id'),
                    'invoice_id' => Http::int('invoice_id') ?: null,
                    'reservation_id' => Http::int('reservation_id') ?: null,
                    'rental_id' => Http::int('rental_id') ?: null,
                    'related_transaction_id' => Http::int('related_transaction_id') ?: null,
                    'transaction_type' => Http::str('transaction_type'),
                    'adjustment_direction' => Http::str('adjustment_direction'),
                    'amount' => Http::str('amount'),
                    'payment_method' => Http::str('payment_method') ?: null,
                    'external_reference' => Http::str('external_reference'),
                    'provider_name' => Http::str('provider_name'),
                    'reason' => Http::str('reason'),
                ]);
                $awaitingApproval = ($entry['status'] ?? '') === 'awaiting_approval';
                Http::flash('success', $awaitingApproval
                    ? 'Financial request submitted for independent approval. It will affect balances only after approval.'
                    : 'Ledger entry posted. Posted rows cannot be edited; request an approved refund or adjustment if a correction is needed.');
                Http::redirect('finance');
            } catch (InvalidArgumentException $e) {
                Http::flash('danger', $e->getMessage());
            }
        }
        View::render('finance/form', ['title' => 'Post transaction', 'invoice' => $invoice, 'customers' => $customers, 'openInvoices' => $openInvoices, 'payments' => $payments]);
    }
}
