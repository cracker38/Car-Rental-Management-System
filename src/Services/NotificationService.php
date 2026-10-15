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

    public function markRead(int $id, ?int $userId, ?int $customerId): void
    {
        Database::execute(
            'UPDATE notifications SET status = \'read\', read_at = NOW()
             WHERE notification_id = ? AND (user_id = ? OR customer_id = ?)',
            [$id, $userId, $customerId]
        );
    }
}
