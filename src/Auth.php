<?php
declare(strict_types=1);

namespace Crms;

final class Auth
{
    public static function user(): ?array
    {
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            return null;
        }
        static $cached = null;
        static $cachedId = null;
        if ($cachedId === $id && $cached) {
            return $cached;
        }
        $user = Database::fetch(
            'SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id WHERE u.user_id = ?',
            [$id]
        );
        $cached = $user;
        $cachedId = $id;
        return $user;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int) $u['user_id'] : null;
    }

    public static function check(): bool
    {
        $u = self::user();
        return $u !== null && $u['status'] === 'active';
    }

    public static function mustChangePassword(): bool
    {
        $u = self::user();
        return $u !== null && (int) ($u['must_change_password'] ?? 0) === 1;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Http::flash('warning', 'Please sign in to continue.');
            Http::redirect('login');
        }
    }

    public static function isCustomer(): bool
    {
        $u = self::user();
        return $u !== null && $u['role_name'] === 'Customer';
    }

    public static function customerId(): ?int
    {
        $id = self::id();
        if (!$id) {
            return null;
        }
        static $cachedUserId = null;
        static $cachedCustomerId = null;
        if ($cachedUserId === $id) {
            return $cachedCustomerId;
        }
        $row = Database::fetch('SELECT customer_id FROM customers WHERE linked_user_id = ?', [$id]);
        $cachedUserId = $id;
        $cachedCustomerId = $row ? (int) $row['customer_id'] : null;
        return $cachedCustomerId;
    }

    public static function login(string $email, string $password): bool
    {
        $email = strtolower(trim($email));
        if (self::isThrottled($email)) {
            Audit::log('login.throttled', 'user', $email, 'Too many failed attempts', null, null, 'denied');
            Http::flash('danger', 'Too many failed sign-in attempts. Try again later.');
            return false;
        }

        $user = Database::fetch('SELECT * FROM users WHERE email = ?', [$email]);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            Audit::log('login.failed', 'user', $email, 'Invalid credentials', null, null, 'failure');
            Http::flash('danger', 'Invalid email or password.');
            return false;
        }
        if ($user['status'] !== 'active') {
            Audit::log('login.blocked', 'user', (string) $user['user_id'], 'Inactive or suspended account', null, null, 'denied');
            Http::flash('danger', 'This account cannot sign in.');
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['user_id'];
        Database::execute('UPDATE users SET last_login_at = NOW() WHERE user_id = ?', [$user['user_id']]);
        Audit::log('login.success', 'user', (string) $user['user_id'], 'Signed in');
        return true;
    }

    public static function logout(): void
    {
        Audit::log('logout', 'user', self::id() ? (string) self::id() : null, 'Signed out');
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    private static function isThrottled(string $email): bool
    {
        $max = (int) Settings::get('login_max_attempts', '5');
        $window = (int) Settings::get('login_window_minutes', '15');
        $row = Database::fetch(
            "SELECT COUNT(*) AS c FROM audit_logs
             WHERE action = 'login.failed' AND entity_id = ? AND ip_address = ?
               AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            [$email, Http::ip(), $window]
        );
        return $row !== null && (int) $row['c'] >= $max;
    }
}
