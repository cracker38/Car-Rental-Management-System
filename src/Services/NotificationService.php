<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Database;

final class NotificationService
{
    public function notifyUser(?int $userId, ?int $customerId, string $type, string $subject, string $message, ?string $entity = null, ?int $entityId = null): void
    {
        Database::execute(
            'INSERT INTO notifications (user_id, customer_id, channel, notification_type, subject, message, related_entity_type, related_entity_id, status, sent_at)
             VALUES (?,?,\'in_app\',?,?,?,?,?,\'sent\', NOW())',
            [$userId, $customerId, $type, $subject, $message, $entity, $entityId]
        );
    }

    public function notifyRentalOfficers(string $type, string $subject, string $message, ?string $entity = null, ?int $entityId = null, ?int $excludeUserId = null): void
    {
        $sql = "SELECT u.user_id FROM users u
                JOIN roles r ON r.role_id = u.role_id
                WHERE r.role_name = 'Rental Officer' AND u.status = 'active'";
        $params = [];
        if ($excludeUserId !== null) {
            $sql .= ' AND u.user_id <> ?';
            $params[] = $excludeUserId;
        }

        foreach (Database::fetchAll($sql, $params) as $officer) {
            $this->notifyUser((int) $officer['user_id'], null, $type, $subject, $message, $entity, $entityId);
        }
    }

    public function markRead(int $id, ?int $userId, ?int $customerId): void
    {
        Database::execute(
            'UPDATE notifications SET status = \'read\', read_at = NOW()
             WHERE notification_id = ? AND (user_id = ? OR customer_id = ?)',
            [$id, $userId, $customerId]
        );
    }
}
