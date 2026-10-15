<?php
declare(strict_types=1);

namespace Crms\Controllers;

use Crms\Auth;
use Crms\Database;
use Crms\Http;
use Crms\Rbac;
use Crms\View;
use Crms\Services\InvoiceService;
use Crms\Services\SmsService;
use InvalidArgumentException;

final class InvoiceController
{
    public function index(): void
    {
        Rbac::require('invoices.view');
        $sql = 'SELECT i.*, c.first_name, c.last_name, b.outstanding_balance, b.net_paid
                FROM invoices i
                JOIN customers c ON c.customer_id = i.customer_id
                LEFT JOIN vw_invoice_balances b ON b.invoice_id = i.invoice_id';
        $params = [];
        if (Auth::isCustomer()) {
            $sql .= ' WHERE i.customer_id = ?';
            $params[] = Auth::customerId() ?: 0;
        }
        $sql .= ' ORDER BY i.created_at DESC LIMIT 200';
        View::render('invoices/index', ['title' => 'Invoices', 'rows' => Database::fetchAll($sql, $params)]);
    }

    public function show(): void
    {
        Rbac::require('invoices.view');
        $id = Http::int('id');
        Rbac::assertCustomerOwns('invoice', $id);
        $row = Database::fetch(
            'SELECT i.*, c.first_name, c.last_name, c.phone, c.email, b.outstanding_balance, b.net_paid
             FROM invoices i
             JOIN customers c ON c.customer_id = i.customer_id
             LEFT JOIN vw_invoice_balances b ON b.invoice_id = i.invoice_id
             WHERE i.invoice_id = ?',
            [$id]
        );
        if (!$row) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Not found']);
            return;
        }
        $items = Database::fetchAll('SELECT * FROM invoice_items WHERE invoice_id = ?', [$id]);
        $txns = Database::fetchAll('SELECT * FROM financial_transactions WHERE invoice_id = ? ORDER BY transaction_date', [$id]);
        View::render('invoices/show', ['title' => $row['invoice_number'], 'row' => $row, 'items' => $items, 'txns' => $txns]);
    }

    public function issue(): void
    {
        Rbac::require('invoices.manage');
        try {
            (new InvoiceService())->issue(Http::int('id'));
            $inv = Database::fetch('SELECT * FROM invoices WHERE invoice_id = ?', [Http::int('id')]);
            if ($inv) {
                (new SmsService())->sendToCustomer(
                    (int) $inv['customer_id'],
                    'invoice_issued',
                    'Invoice ' . $inv['invoice_number'],
                    'Invoice ' . $inv['invoice_number'] . ' of RWF ' . (int) $inv['total_amount'] . ' has been issued.',
                    'invoice',
                    (int) $inv['invoice_id']
                );
            }
            Http::flash('success', 'Invoice issued.');
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
        }
        Http::redirect('invoices/show', ['id' => Http::int('id')]);
    }
}
