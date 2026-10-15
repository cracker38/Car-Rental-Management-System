<?php
declare(strict_types=1);

use Crms\Auth;
use Crms\Csrf;
use Crms\Http;

$appCfg = require dirname(__DIR__) . '/config/app.php';
date_default_timezone_set($appCfg['timezone']);

spl_autoload_register(function (string $class): void {
    $prefix = 'Crms\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $rel = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = __DIR__ . '/' . $rel . '.php';
    if (is_file($file)) {
        require $file;
    }
});

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $route = 'home', array $query = []): string
    {
        $base = rtrim(crms_base_url(), '/');
        $query['r'] = $route;
        return $base . '/index.php?' . http_build_query($query);
    }
}

if (!function_exists('crms_base_url')) {
    function crms_base_url(): string
    {
        $cfg = require dirname(__DIR__) . '/config/app.php';
        if (!empty($cfg['base_url'])) {
            return rtrim((string) $cfg['base_url'], '/');
        }
        $script = $_SERVER['SCRIPT_NAME'] ?? '/CarrentalMS/index.php';
        $dir = str_replace('\\', '/', dirname($script));
        if ($dir === '/' || $dir === '\\' || $dir === '.') {
            return '';
        }
        return rtrim($dir, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return rtrim(crms_base_url(), '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('can')) {
    function can(string $permission): bool
    {
        return \Crms\Rbac::can($permission);
    }
}

if (!function_exists('nav_active')) {
    function nav_active(string ...$routes): string
    {
        $current = (string) ($_GET['r'] ?? 'home');
        foreach ($routes as $route) {
            if ($current === $route || str_starts_with($current, $route . '/')) {
                return 'active';
            }
        }
        return '';
    }
}

if (!function_exists('status_badge')) {
    function status_badge(string $status): string
    {
        $cls = \Crms\Ui::statusClass($status);
        return '<span class="badge text-bg-' . e($cls) . ' badge-status">' . e($status) . '</span>';
    }
}

if (!function_exists('vehicle_img')) {
    function vehicle_img(?string $path): string
    {
        return \Crms\Ui::vehicleImage($path);
    }
}

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443);

session_name($appCfg['session_name']);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (Http::isPost()) {
    Csrf::verify();
}

unset($appCfg, $https);
