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
        $reservationId = Http::int('reservation_id');
        $customerId = Http::int('customer_id');
        $returnToDashboard = Auth::isCustomer() && Http::str('return_to') === 'dashboard';
        if ($invoiceId && $reservationId) {
            Http::flash('danger', 'A payment request can only apply to one invoice or reservation at a time.');
            Http::redirect('reservations/show', ['id' => $reservationId]);
        }
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
        } elseif (Auth::isCustomer() && $reservationId && Http::str('transaction_type') === 'deposit_received') {
            Rbac::assertCustomerOwns('reservation', $reservationId);
            $reservation = Database::fetch('SELECT reservation_id, customer_id, status, approval_note, approver_signature, customer_signature FROM reservations WHERE reservation_id = ?', [$reservationId]);
            if (!$reservation || $reservation['status'] !== 'confirmed' || trim((string) ($reservation['approval_note'] ?? '')) === '' || empty($reservation['approver_signature']) || empty($reservation['customer_signature'])) {
                Http::flash('danger', 'This reservation must have a signed approval decision before payment.');
                Http::redirect('reservations/show', ['id' => $reservationId]);
            }
            $customerId = (int) $reservation['customer_id'];
        } else {
            Rbac::require('transactions.manage');
        }

        try {
            $row = (new HdevPaymentService())->initiate([
                'customer_id' => $customerId,
                'invoice_id' => $invoiceId ?: null,
                'reservation_id' => $reservationId ?: null,
                'rental_id' => Http::int('rental_id') ?: null,
                'transaction_type' => Http::str('transaction_type') ?: 'payment',
                'amount' => Http::str('amount'),
                'phone' => Http::str('phone'),
                'reason' => Http::str('reason') ?: 'HDEV mobile money',
            ]);
            (new HdevPaymentService())->notifyIfCompleted(['status' => 'pending'], $row);
            if (($row['status'] ?? '') === 'completed') {
                Http::flash('success', $reservationId ? 'Security deposit confirmed. The ledger is up to date.' : 'Mobile Money payment confirmed. The invoice and ledger are up to date.');
            } elseif (($row['status'] ?? '') === 'failed') {
                Http::flash('danger', 'HDEV rejected or failed the request. Check the phone number and try again.');
            } else {
                Http::flash('success', 'Approve the MoMo prompt on the phone. This page will refresh when HDEV confirms. Ref ' . ($row['external_reference'] ?? ''));
            }
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
        }
        if ($invoiceId) {
            Http::redirect($returnToDashboard ? 'dashboard' : 'invoices/show', $returnToDashboard ? [] : ['id' => $invoiceId]);
        }
        if ($reservationId) {
            Http::redirect('reservations/show', ['id' => $reservationId]);
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
        if (!empty($row['reservation_id'])) {
            Http::redirect('reservations/show', ['id' => $row['reservation_id']]);
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
            'SELECT ft.*, c.first_name, c.last_name, c.phone, c.email, i.invoice_number, r.reservation_code
             FROM financial_transactions ft JOIN customers c ON c.customer_id=ft.customer_id
             LEFT JOIN invoices i ON i.invoice_id=ft.invoice_id AND i.customer_id=ft.customer_id
             LEFT JOIN rentals rt ON rt.rental_id=ft.rental_id AND rt.customer_id=ft.customer_id
             LEFT JOIN reservations r ON r.reservation_id=COALESCE(ft.reservation_id,i.reservation_id,rt.reservation_id) AND r.customer_id=ft.customer_id
             WHERE ft.transaction_id=?',
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
        \Crms\View::render('payments/receipt', [
            'title' => 'Receipt ' . $row['transaction_number'], 'row' => $row, 'printReceipt' => true,
        ]);
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
