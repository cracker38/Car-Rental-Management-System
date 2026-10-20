<?php
declare(strict_types=1);

$local = [];
$localFile = __DIR__ . '/pawapay.local.php';
if (is_file($localFile) && is_readable($localFile)) {
    $loaded = require $localFile;
    $local = is_array($loaded) ? $loaded : [];
}
$env = static function (string $name, string $fallback = ''): string {
    $value = getenv($name);
    return $value !== false && trim($value) !== '' ? trim($value) : $fallback;
};

return [
    'base_url' => rtrim($env('PAWAPAY_BASE_URL', (string) ($local['base_url'] ?? 'https://api.pawapay.io')), '/'),
    'api_token' => $env('PAWAPAY_API_TOKEN', (string) ($local['api_token'] ?? '')),
];
