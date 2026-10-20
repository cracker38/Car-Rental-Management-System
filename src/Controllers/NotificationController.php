<?php
declare(strict_types=1);

namespace Crms\Controllers;

use Crms\Auth;
use Crms\Database;
use Crms\Http;
use Crms\Services\NotificationService;

final class NotificationController
{
    public function poll(): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');
        echo json_encode([
            'unread' => (new NotificationService())->unreadCount(Auth::id(), Auth::customerId()),
        ]);
    }

    public function open(): void
    {
        Auth::requireLogin();
        if (!Http::isPost()) {
            Http::redirect('dashboard');
        }
        $id = Http::int('id');
        $userId = Auth::id() ?: 0;
        $customerId = Auth::customerId() ?: 0;
        $note = Database::fetch(
            'SELECT * FROM notifications WHERE notification_id = ? AND (user_id = ? OR customer_id = ?)',
            [$id, $userId, $customerId]
        );
        if (!$note) {
            http_response_code(404);
            Http::flash('warning', 'That notification is no longer available.');
            Http::redirect('dashboard');
        }
        (new NotificationService())->markRead($id, $userId ?: null, $customerId ?: null);
        $entityId = (int) ($note['related_entity_id'] ?? 0);
        $target = match ($note['related_entity_type'] ?? '') {
            'reservation' => $this->reservationTarget($entityId),
            'invoice' => $entityId > 0 ? url('invoices/show', ['id' => $entityId]) : url('dashboard'),
            'rental' => $entityId > 0 ? url('rentals/show', ['id' => $entityId]) : url('dashboard'),
            'financial_transaction' => $this->transactionTarget($entityId),
            default => url('dashboard'),
        };
        header('Location: ' . $target);
        exit;
    }

    private function reservationTarget(int $id): string
    {
        if ((Auth::user()['role_name'] ?? '') === 'Driver') {
            return url('drivers/schedule');
        }
        return $id > 0 ? url('reservations/show', ['id' => $id]) : url('dashboard');
    }

    private function transactionTarget(int $id): string
    {
        if ($id <= 0) {
            return url('dashboard');
        }
        $transaction = Database::fetch(
            'SELECT ft.status, ft.invoice_id, ft.reservation_id, i.reservation_id AS invoice_reservation_id
             FROM financial_transactions ft LEFT JOIN invoices i ON i.invoice_id = ft.invoice_id
             WHERE ft.transaction_id = ?',
            [$id]
        );
        if (!$transaction) {
            return url('dashboard');
        }
        $reservationId = (int) ($transaction['reservation_id'] ?? $transaction['invoice_reservation_id'] ?? 0);
        if (Auth::isCustomer() && $transaction['status'] === 'completed') {
            return url('payments/receipt', ['id' => $id]);
        }
        if ($transaction['status'] === 'completed' && \Crms\Rbac::can('transactions.view')) {
            return url('payments/receipt', ['id' => $id]);
        }
        if ($reservationId > 0 && \Crms\Rbac::can('reservations.view')) {
            return url('reservations/show', ['id' => $reservationId]);
        }
        if (!empty($transaction['invoice_id']) && \Crms\Rbac::can('invoices.view')) {
            return url('invoices/show', ['id' => $transaction['invoice_id']]);
        }
        if (\Crms\Rbac::can('transactions.view') || \Crms\Rbac::can('transactions.manage')) {
            return url('finance');
        }
        return url('finance');
    }
}
