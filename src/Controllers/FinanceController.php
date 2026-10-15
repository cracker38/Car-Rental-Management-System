<?php
declare(strict_types=1);

namespace Crms\Controllers;

use Crms\Database;
use Crms\Http;
use Crms\Rbac;
use Crms\View;
use Crms\Services\FinanceService;
use InvalidArgumentException;

final class FinanceController
{
    public function index(): void
    {
        Rbac::require('transactions.manage');
        $rows = Database::fetchAll(
            'SELECT t.*, c.first_name, c.last_name FROM financial_transactions t
             JOIN customers c ON c.customer_id = t.customer_id
             ORDER BY t.transaction_date DESC LIMIT 300'
        );
        View::render('finance/index', ['title' => 'Ledger', 'rows' => $rows]);
    }

    public function form(): void
    {
        Rbac::require('transactions.manage');
        $invoice = null;
        if (Http::int('invoice_id')) {
            $invoice = Database::fetch(
                'SELECT i.*, b.outstanding_balance FROM invoices i
                 LEFT JOIN vw_invoice_balances b ON b.invoice_id = i.invoice_id WHERE i.invoice_id = ?',
                [Http::int('invoice_id')]
            );
        }
        $customers = Database::fetchAll('SELECT customer_id, customer_code, first_name, last_name FROM customers ORDER BY last_name');
        $payments = Database::fetchAll("SELECT transaction_id, transaction_number, amount FROM financial_transactions WHERE transaction_type='payment' AND status='completed' ORDER BY transaction_id DESC LIMIT 50");
        if (Http::isPost()) {
            try {
                (new FinanceService())->post([
                    'customer_id' => Http::int('customer_id'),
                    'invoice_id' => Http::int('invoice_id') ?: null,
                    'reservation_id' => Http::int('reservation_id') ?: null,
                    'rental_id' => Http::int('rental_id') ?: null,
                    'related_transaction_id' => Http::int('related_transaction_id') ?: null,
                    'transaction_type' => Http::str('transaction_type'),
                    'amount' => Http::str('amount'),
                    'payment_method' => Http::str('payment_method') ?: null,
                    'external_reference' => Http::str('external_reference'),
                    'provider_name' => Http::str('provider_name'),
                    'reason' => Http::str('reason'),
                ]);
                Http::flash('success', 'Ledger entry posted. Posted rows cannot be edited; use a reversal if needed.');
                Http::redirect('finance');
            } catch (InvalidArgumentException $e) {
                Http::flash('danger', $e->getMessage());
            }
        }
        View::render('finance/form', ['title' => 'Post transaction', 'invoice' => $invoice, 'customers' => $customers, 'payments' => $payments]);
    }
}
