<?php
declare(strict_types=1);

namespace Crms;

final class Rbac
{
    /** @var array<string,bool>|null */
    private static ?array $perms = null;
    private static ?int $permsRoleId = null;

    public static function can(string $permission): bool
    {
        $user = Auth::user();
        if (!$user || $user['status'] !== 'active') {
            return false;
        }
        $roleId = (int) $user['role_id'];
        if (self::$perms === null || self::$permsRoleId !== $roleId) {
            $rows = Database::fetchAll(
                'SELECT p.permission_key FROM role_permissions rp
                 JOIN permissions p ON p.permission_id = rp.permission_id
                 WHERE rp.role_id = ?',
                [$roleId]
            );
            self::$perms = [];
            foreach ($rows as $r) {
                self::$perms[$r['permission_key']] = true;
            }
            self::$permsRoleId = $roleId;
        }
        if (isset(self::$perms[$permission])) {
            return true;
        }
        $viewToManage = [
            'rentals.view' => 'rentals.manage',
            'transactions.view' => 'transactions.manage',
            'maintenance.view' => 'maintenance.manage',
        ];
        $managePermission = $viewToManage[$permission] ?? null;
        return $managePermission !== null && isset(self::$perms[$managePermission]);
    }

    public static function require(string $permission): void
    {
        Auth::requireLogin();
        if (!self::can($permission)) {
            Audit::log('authz.denied', 'permission', $permission, 'Missing permission', null, null, 'denied');
            http_response_code(403);
            View::render('errors/403', ['title' => 'Forbidden']);
            exit;
        }
    }

    public static function assertCustomerOwns(string $entity, int $id): void
    {
        if (!Auth::isCustomer()) {
            return;
        }
        $cid = Auth::customerId();
        if (!$cid) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Forbidden']);
            exit;
        }
        $ok = false;
        if ($entity === 'reservation') {
            $row = Database::fetch('SELECT customer_id FROM reservations WHERE reservation_id = ?', [$id]);
            $ok = $row && (int) $row['customer_id'] === $cid;
        } elseif ($entity === 'invoice') {
            $row = Database::fetch('SELECT customer_id FROM invoices WHERE invoice_id = ?', [$id]);
            $ok = $row && (int) $row['customer_id'] === $cid;
        } elseif ($entity === 'rental') {
            $row = Database::fetch('SELECT customer_id FROM rentals WHERE rental_id = ?', [$id]);
            $ok = $row && (int) $row['customer_id'] === $cid;
        }
        if (!$ok) {
            Audit::log('authz.ownership_denied', $entity, (string) $id, 'Customer ownership check failed', null, null, 'denied');
            http_response_code(403);
            View::render('errors/403', ['title' => 'Forbidden']);
            exit;
        }
    }
}
