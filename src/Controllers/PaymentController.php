<?php
declare(strict_types=1);

namespace Crms\Controllers;

use Crms\Auth;
use Crms\Database;
use Crms\Http;
use Crms\Rbac;
use Crms\Services\HdevPaymentService;
use InvalidArgumentException;

final class PaymentController
{
    public function momo(): void
    {
        Auth::requireLogin();
        $invoiceId = Http::int('invoice_id');
        $customerId = Http::int('customer_id');
        if ($invoiceId) {
            Rbac::assertCustomerOwns('invoice', $invoiceId);
            $invoice = Database::fetch('SELECT * FROM invoices WHERE invoice_id = ?', [$invoiceId]);
            if (!$invoice) {
                Http::flash('danger', 'Invoice not found.');
                Http::redirect('invoices');
            }
            $customerId = (int) $invoice['customer_id'];
            if (!Auth::isCustomer()) {
                Rbac::require('transactions.manage');
            } else {
                Rbac::require('invoices.view');
            }
        } else {
            Rbac::require('transactions.manage');
        }

        try {
            $row = (new HdevPaymentService())->initiate([
                'customer_id' => $customerId,
                'invoice_id' => $invoiceId ?: null,
                'reservation_id' => Http::int('reservation_id') ?: null,
                'rental_id' => Http::int('rental_id') ?: null,
                'transaction_type' => Http::str('transaction_type') ?: 'payment',
                'amount' => Http::str('amount'),
                'phone' => Http::str('phone'),
                'reason' => Http::str('reason') ?: 'HDEV mobile money',
            ]);
            if (($row['status'] ?? '') === 'completed') {
                Http::flash('success', 'HDEV confirmed the payment. The ledger was updated.');
            } else {
                Http::flash('success', 'MoMo prompt sent. Status stays pending until HDEV confirms (callback or Check status). Ref ' . ($row['external_reference'] ?? ''));
            }
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
        }
        if ($invoiceId) {
            Http::redirect('invoices/show', ['id' => $invoiceId]);
        }
        Http::redirect('finance');
    }

    public function sync(): void
    {
        Auth::requireLogin();
        $id = Http::int('id');
        $row = Database::fetch('SELECT * FROM financial_transactions WHERE transaction_id = ?', [$id]);
        if (!$row) {
            Http::flash('danger', 'Transaction not found.');
            Http::redirect('finance');
        }
        if (Auth::isCustomer()) {
            if ((int) $row['customer_id'] !== (int) Auth::customerId()) {
                http_response_code(403);
                exit('Forbidden');
            }
        } else {
            Rbac::require('transactions.manage');
        }
        try {
            $updated = (new HdevPaymentService())->settleByReference((string) $row['external_reference']);
            $status = $updated['status'] ?? $row['status'];
            Http::flash($status === 'completed' ? 'success' : 'info', 'HDEV status: ' . $status . '.');
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
        }
        if (!empty($row['invoice_id'])) {
            Http::redirect('invoices/show', ['id' => $row['invoice_id']]);
        }
        Http::redirect('finance');
    }

    public function receipt(): void
    {
        Auth::requireLogin();
        $id = Http::int('id');
        $row = Database::fetch(
            'SELECT ft.*, c.first_name, c.last_name, c.phone, c.email, i.invoice_number
             FROM financial_transactions ft JOIN customers c ON c.customer_id=ft.customer_id
             LEFT JOIN invoices i ON i.invoice_id=ft.invoice_id WHERE ft.transaction_id=?',
            [$id]
        );
        if (!$row) {
            http_response_code(404);
            \Crms\View::render('errors/404', ['title' => 'Not found']);
            return;
        }
        if (Auth::isCustomer()) {
            if ((int)$row['customer_id'] !== (int)Auth::customerId() || $row['status'] !== 'completed') {
                http_response_code(403);
                \Crms\View::render('errors/403', ['title' => 'Forbidden']);
                return;
            }
        } else {
            Rbac::require('transactions.manage');
        }
        if ($row['status'] !== 'completed') {
            Http::flash('warning', 'A receipt is available after the transaction is completed.');
            Http::redirect(!empty($row['invoice_id']) ? 'invoices/show' : 'finance', !empty($row['invoice_id']) ? ['id' => $row['invoice_id']] : []);
        }
        \Crms\View::render('payments/receipt', ['title' => 'Receipt ' . $row['transaction_number'], 'row' => $row]);
    }
}
