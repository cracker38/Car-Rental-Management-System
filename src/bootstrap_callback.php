<?php
declare(strict_types=1);

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

if (!function_exists('crms_base_url')) {
    function crms_base_url(): string
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '/CarrentalMS/hdev_callback.php';
        $dir = str_replace('\\', '/', dirname($script));
        if ($dir === '/' || $dir === '\\' || $dir === '.') {
            return '';
        }
        return rtrim($dir, '/');
    }
}
