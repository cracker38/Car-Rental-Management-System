<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Database;
use RuntimeException;
use Throwable;

final class NotificationService
{
    /** @var list<int> */
    private static array $queuedEmailIds = [];
    private static bool $dispatcherRegistered = false;

    public function forUser(?int $userId, ?int $customerId, int $limit = 8): array
    {
        $limit = max(1, min(50, $limit));
        return Database::fetchAll(
            "SELECT * FROM notifications WHERE channel = 'in_app' AND (user_id = ? OR customer_id = ?) ORDER BY created_at DESC, notification_id DESC LIMIT " . $limit,
            [$userId ?: 0, $customerId ?: 0]
        );
    }

    public function unreadCount(?int $userId, ?int $customerId): int
    {
        $row = Database::fetch(
            "SELECT COUNT(*) AS total FROM notifications
             WHERE channel = 'in_app' AND status <> 'read' AND (user_id = ? OR customer_id = ?)",
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
        $this->sendEmailNotification($userId, $customerId, $type, $subject, $message, $entity, $entityId);
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
             WHERE notification_id = ? AND channel = \'in_app\' AND (user_id = ? OR customer_id = ?)',
            [$id, $userId, $customerId]
        );
    }

    private function sendEmailNotification(?int $userId, ?int $customerId, string $type, string $subject, string $message, ?string $entity, ?int $entityId): void
    {
        Database::execute(
            'INSERT INTO notifications (user_id, customer_id, channel, notification_type, subject, message, related_entity_type, related_entity_id, status, sent_at, error_message)
             VALUES (?,?,\'email\',?,?,?,?,?,\'queued\',NULL,NULL)',
            [$userId, $customerId, $type, $subject, $message, $entity, $entityId]
        );
        self::$queuedEmailIds[] = (int) Database::lastId();
        if (!self::$dispatcherRegistered) {
            self::$dispatcherRegistered = true;
            register_shutdown_function([self::class, 'dispatchQueuedEmails']);
        }
    }

    public static function dispatchQueuedEmails(): void
    {
        if (Database::pdo()->inTransaction()) {
            return;
        }

        $queuedIds = array_values(array_unique(array_filter(self::$queuedEmailIds, static fn(int $id): bool => $id > 0)));
        self::$queuedEmailIds = [];
        if (!$queuedIds) {
            $queuedIds = array_map(
                static fn(array $row): int => (int) $row['notification_id'],
                Database::fetchAll("SELECT notification_id FROM notifications WHERE channel='email' AND status='queued' ORDER BY created_at, notification_id LIMIT 20")
            );
        }

        foreach ($queuedIds as $id) {
            $row = Database::fetch(
                "SELECT notification_id, user_id, customer_id, subject, message FROM notifications
                 WHERE notification_id = ? AND channel = 'email' AND status = 'queued'",
                [$id]
            );
            if (!$row) {
                continue;
            }
            try {
                $recipient = self::emailRecipient(
                    $row['user_id'] !== null ? (int) $row['user_id'] : null,
                    $row['customer_id'] !== null ? (int) $row['customer_id'] : null
                );
                (new EmailService())->send($recipient, (string) $row['subject'], (string) $row['message']);
                Database::execute(
                    "UPDATE notifications SET status='sent', sent_at=NOW(), error_message=NULL WHERE notification_id=? AND status='queued'",
                    [$id]
                );
            } catch (Throwable $e) {
                $error = substr(trim(preg_replace('/[\x00-\x1F\x7F]+/', ' ', $e->getMessage()) ?? 'Email send failed.'), 0, 500);
                Database::execute(
                    "UPDATE notifications SET status='failed', error_message=? WHERE notification_id=? AND status='queued'",
                    [$error, $id]
                );
            }
        }
    }

    private static function emailRecipient(?int $userId, ?int $customerId): string
    {
        if ($customerId !== null && $customerId > 0) {
            $row = Database::fetch(
                'SELECT c.email AS customer_email, u.email AS account_email
                 FROM customers c LEFT JOIN users u ON u.user_id = c.linked_user_id
                 WHERE c.customer_id = ?',
                [$customerId]
            );
            foreach ([$row['customer_email'] ?? '', $row['account_email'] ?? ''] as $email) {
                $email = trim((string) $email);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return $email;
                }
            }
        } elseif ($userId !== null && $userId > 0) {
            $row = Database::fetch('SELECT email FROM users WHERE user_id = ?', [$userId]);
            $email = trim((string) ($row['email'] ?? ''));
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }
        throw new RuntimeException('No valid recipient email address is saved for this notification.');
    }
}
