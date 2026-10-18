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
        if (!Http::isPost()) {
            Http::flash('danger', 'Send the Mobile Money form to start a payment.');
            Http::redirect('finance');
        }
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
                Http::flash('success', 'Mobile Money payment confirmed. The invoice and ledger are up to date.');
            } elseif (($row['status'] ?? '') === 'failed') {
                Http::flash('danger', 'HDEV rejected or failed the request. Check the phone number and try again.');
            } else {
                Http::flash('success', 'Approve the MoMo prompt on the phone. This page will refresh when HDEV confirms. Ref ' . ($row['external_reference'] ?? ''));
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
        if (!Auth::isCustomer()) {
            Rbac::require('transactions.manage');
        }
        if (!Http::isPost()) {
            Http::flash('danger', 'Use Check status to refresh a pending payment.');
            Http::redirect('finance');
        }
        $id = Http::int('id');
        $row = Database::fetch('SELECT * FROM financial_transactions WHERE transaction_id = ?', [$id]);
        if (!$row) {
            Http::flash('danger', 'Transaction not found.');
            Http::redirect('finance');
        }
        if (!$this->canAccess($row)) {
            http_response_code(403);
            \Crms\View::render('errors/403', ['title' => 'Forbidden']);
            return;
        }
        try {
            $service = new HdevPaymentService();
            $updated = $service->settleByReference((string) $row['external_reference']);
            $service->notifyIfCompleted($row, $updated);
            $status = $updated['status'] ?? $row['status'];
            if ($status === 'completed') {
                Http::flash('success', 'Payment confirmed by HDEV.');
            } elseif ($status === 'failed') {
                Http::flash('danger', 'HDEV reported this payment as failed.');
            } else {
                Http::flash('info', 'Still waiting for the customer to approve the MoMo prompt.');
            }
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
        }
        if (!empty($row['invoice_id'])) {
            Http::redirect('invoices/show', ['id' => $row['invoice_id']]);
        }
        Http::redirect('finance');
    }

    public function status(): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json; charset=utf-8');
        $id = Http::int('id');
        $row = Database::fetch('SELECT * FROM financial_transactions WHERE transaction_id = ?', [$id]);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Not found']);
            return;
        }
        if (!$this->canAccess($row)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
            return;
        }
        $updated = $row;
        $canSettle = Auth::isCustomer() || Rbac::can('transactions.manage');
        if ($canSettle && $row['status'] === 'pending' && $row['external_reference']) {
            try {
                $service = new HdevPaymentService();
                $updated = $service->settleByReference((string) $row['external_reference']) ?? $row;
                $service->notifyIfCompleted($row, $updated);
            } catch (InvalidArgumentException $e) {
                echo json_encode(['status' => 'error', 'ledger_status' => $row['status'], 'message' => $e->getMessage()]);
                return;
            }
        }
        echo json_encode([
            'status' => 'ok',
            'ledger_status' => $updated['status'] ?? $row['status'],
            'transaction_id' => (int) ($updated['transaction_id'] ?? $id),
            'reference' => $updated['external_reference'] ?? $row['external_reference'],
        ]);
    }

    public function receipt(): void
    {
        Auth::requireLogin();
        $id = Http::int('id');
        $row = Database::fetch(
            'SELECT ft.*, c.first_name, c.last_name, c.phone, c.email, i.invoice_number
             FROM financial_transactions ft JOIN customers c ON c.customer_id=ft.customer_id
             LEFT JOIN invoices i ON i.invoice_id=ft.invoice_id AND i.customer_id=ft.customer_id WHERE ft.transaction_id=?',
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
            Rbac::require('transactions.view');
        }
        if ($row['status'] !== 'completed') {
            Http::flash('warning', 'A receipt is available after the transaction is completed.');
            Http::redirect(!empty($row['invoice_id']) ? 'invoices/show' : 'finance', !empty($row['invoice_id']) ? ['id' => $row['invoice_id']] : []);
        }
        \Crms\View::render('payments/receipt', ['title' => 'Receipt ' . $row['transaction_number'], 'row' => $row]);
    }

    private function canAccess(array $row): bool
    {
        if (Auth::isCustomer()) {
            return (int) $row['customer_id'] === (int) Auth::customerId();
        }
        if (Rbac::can('transactions.manage') || Rbac::can('transactions.view')) {
            return true;
        }
        return !empty($row['invoice_id']) && Rbac::can('invoices.view');
    }
}
