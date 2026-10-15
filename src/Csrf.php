<?php
declare(strict_types=1);

namespace Crms;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function verify(): void
    {
        $sent = $_POST['_csrf'] ?? '';
        $ok = is_string($sent) && hash_equals(self::token(), $sent);
        if (!$ok) {
            Audit::log('csrf.rejected', 'security', null, 'Invalid CSRF token', null, null, 'denied');
            http_response_code(419);
            exit('Invalid security token. Refresh the page and try again.');
        }
    }
}
