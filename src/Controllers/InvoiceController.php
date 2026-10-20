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
            $sql .= " WHERE i.customer_id = ? AND i.status NOT IN ('draft','void')";
            $params[] = Auth::customerId() ?: 0;
        }
        $sql .= ' ORDER BY i.created_at DESC LIMIT 200';
        $unbilledRentals = Rbac::can('invoices.manage') ? Database::fetchAll(
            "SELECT rt.rental_id, rt.rental_number, rt.actual_return_datetime, c.first_name, c.last_name
             FROM rentals rt JOIN customers c ON c.customer_id = rt.customer_id
             WHERE rt.status = 'closed' AND (
               (NOT EXISTS (SELECT 1 FROM invoices i WHERE i.rental_id = rt.rental_id AND i.status <> 'void')
                AND NOT EXISTS (SELECT 1 FROM invoices i WHERE i.reservation_id = rt.reservation_id AND i.notes LIKE 'Booking prepayment:%' AND i.status <> 'void'))
               OR
               (EXISTS (SELECT 1 FROM invoices bi WHERE bi.rental_id = rt.rental_id AND bi.notes LIKE 'Booking prepayment:%' AND bi.status <> 'void')
                AND (rt.extra_mileage_fee + rt.late_return_fee + rt.damage_fee + rt.cleaning_fee + rt.other_charges) > 0
                AND NOT EXISTS (SELECT 1 FROM invoices si WHERE si.rental_id = rt.rental_id AND si.notes LIKE 'Additional rental charges:%' AND si.status <> 'void'))
             )
             ORDER BY rt.actual_return_datetime DESC LIMIT 50"
        ) : [];
        View::render('invoices/index', ['title' => 'Invoices', 'rows' => Database::fetchAll($sql, $params), 'unbilledRentals' => $unbilledRentals]);
    }

    public function show(): void
    {
        Rbac::require('invoices.view');
        $id = Http::int('id');
        Rbac::assertCustomerOwns('invoice', $id);
        $invoiceSql = 'SELECT i.*, c.first_name, c.last_name, c.phone, c.email, b.outstanding_balance, b.net_paid,
                              r.status AS reservation_status, r.approval_note, r.approver_signature, r.customer_signature
                       FROM invoices i
                       JOIN customers c ON c.customer_id = i.customer_id
                       LEFT JOIN reservations r ON r.reservation_id = i.reservation_id
                       LEFT JOIN vw_invoice_balances b ON b.invoice_id = i.invoice_id
                       WHERE i.invoice_id = ?';
        $invoiceParams = [$id];
        if (Auth::isCustomer()) {
            $invoiceSql .= " AND i.customer_id = ? AND i.status NOT IN ('draft','void')";
            $invoiceParams[] = Auth::customerId() ?: 0;
        }
        $row = Database::fetch($invoiceSql, $invoiceParams);
        if (!$row) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Not found']);
            return;
        }
        $items = Database::fetchAll('SELECT * FROM invoice_items WHERE invoice_id = ?', [$id]);
        $txnsSql = 'SELECT * FROM financial_transactions WHERE invoice_id = ?';
        $txnsParams = [$id];
        if (Auth::isCustomer()) {
            $txnsSql .= ' AND customer_id = ?';
            $txnsParams[] = Auth::customerId() ?: 0;
        }
        $txnsSql .= ' ORDER BY transaction_date';
        $txns = Database::fetchAll($txnsSql, $txnsParams);
        $pendingMomo = Database::fetch(
            "SELECT COALESCE(SUM(amount),0) AS amount FROM financial_transactions
             WHERE invoice_id = ? AND transaction_type = 'payment' AND payment_method = 'mobile_money' AND status = 'pending'",
            [$id]
        );
        $paymentRequestable = max(0, (float) ($row['outstanding_balance'] ?? 0) - (float) ($pendingMomo['amount'] ?? 0));
        View::render('invoices/show', ['title' => $row['invoice_number'], 'row' => $row, 'items' => $items, 'txns' => $txns, 'pendingMomoAmount' => (float) ($pendingMomo['amount'] ?? 0), 'paymentRequestable' => $paymentRequestable]);
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

    public function create(): void
    {
        Rbac::require('invoices.manage');
        if (!Http::isPost()) {
            Http::redirect('invoices');
        }
        try {
            $invoice = (new InvoiceService())->createFromRental(Http::int('rental_id'));
            Http::flash('success', 'Draft invoice generated from the closed rental. Review and issue it when ready.');
            Http::redirect('invoices/show', ['id' => $invoice['invoice_id']]);
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
            Http::redirect('invoices');
        }
    }
}
