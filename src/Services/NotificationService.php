<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Database;

final class NotificationService
{
    public function forUser(?int $userId, ?int $customerId, int $limit = 8): array
    {
        $limit = max(1, min(50, $limit));
        return Database::fetchAll(
            'SELECT * FROM notifications WHERE user_id = ? OR customer_id = ? ORDER BY created_at DESC, notification_id DESC LIMIT ' . $limit,
            [$userId ?: 0, $customerId ?: 0]
        );
    }

    public function unreadCount(?int $userId, ?int $customerId): int
    {
        $row = Database::fetch(
            "SELECT COUNT(*) AS total FROM notifications
             WHERE status <> 'read' AND (user_id = ? OR customer_id = ?)",
            [$userId ?: 0, $customerId ?: 0]
        );
        return (int) ($row['total'] ?? 0);
    }

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

    public function notifyUsersWithPermission(string $permissionKey, string $type, string $subject, string $message, ?string $entity = null, ?int $entityId = null, ?int $excludeUserId = null): void
    {
        $sql =
            'SELECT DISTINCT u.user_id FROM users u
             JOIN role_permissions rp ON rp.role_id = u.role_id
             JOIN permissions p ON p.permission_id = rp.permission_id
             WHERE p.permission_key = ? AND u.status = \'active\'';
        $params = [$permissionKey];
        if ($excludeUserId !== null) {
            $sql .= ' AND u.user_id <> ?';
            $params[] = $excludeUserId;
        }
        $users = Database::fetchAll($sql, $params);
        foreach ($users as $user) {
            $this->notifyUser((int) $user['user_id'], null, $type, $subject, $message, $entity, $entityId);
        }
    }

    /** @param list<string> $permissionKeys */
    public function notifyUsersWithAnyPermission(array $permissionKeys, string $type, string $subject, string $message, ?string $entity = null, ?int $entityId = null, ?int $excludeUserId = null): void
    {
        $permissionKeys = array_values(array_unique(array_filter($permissionKeys, static fn(string $key): bool => $key !== '')));
        if (!$permissionKeys) {
            return;
        }
        $marks = implode(',', array_fill(0, count($permissionKeys), '?'));
        $sql = "SELECT DISTINCT u.user_id FROM users u
                JOIN role_permissions rp ON rp.role_id = u.role_id
                JOIN permissions p ON p.permission_id = rp.permission_id
                WHERE p.permission_key IN ({$marks}) AND u.status = 'active'";
        $params = $permissionKeys;
        if ($excludeUserId !== null) {
            $sql .= ' AND u.user_id <> ?';
            $params[] = $excludeUserId;
        }
        foreach (Database::fetchAll($sql, $params) as $user) {
            $this->notifyUser((int) $user['user_id'], null, $type, $subject, $message, $entity, $entityId);
        }
    }

    public function notifyRole(string $roleName, string $type, string $subject, string $message, ?string $entity = null, ?int $entityId = null): void
    {
        foreach (Database::fetchAll(
            'SELECT u.user_id FROM users u JOIN roles r ON r.role_id = u.role_id WHERE r.role_name = ? AND u.status = \'active\'',
            [$roleName]
        ) as $user) {
            $this->notifyUser((int) $user['user_id'], null, $type, $subject, $message, $entity, $entityId);
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
