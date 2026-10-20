<?php
$local = [];
$localFile = __DIR__ . '/mail.local.php';
if (is_file($localFile) && is_readable($localFile)) {
    $loaded = require $localFile;
    $local = is_array($loaded) ? $loaded : [];
}
$env = static function (string $name, mixed $fallback = '') use ($local): mixed {
    $value = getenv($name);
    if ($value !== false && trim((string) $value) !== '') {
        return $value;
    }
    return $fallback;
};
$username = (string) $env('CRMS_MAIL_USERNAME', $local['username'] ?? '');

/**
 * Gmail SMTP defaults. Set the account and Google app password through the
 * environment or the ignored config/mail.local.php file.
 */
return [
    'host' => (string) $env('CRMS_MAIL_HOST', $local['host'] ?? 'smtp.gmail.com'),
    'port' => (int) $env('CRMS_MAIL_PORT', $local['port'] ?? 587),
    'encryption' => strtolower((string) $env('CRMS_MAIL_ENCRYPTION', $local['encryption'] ?? 'tls')),
    'username' => $username,
    'password' => (string) $env('CRMS_MAIL_PASSWORD', $local['password'] ?? ''),
    'from_address' => (string) $env('CRMS_MAIL_FROM_ADDRESS', $local['from_address'] ?? $username),
    'from_name' => (string) $env('CRMS_MAIL_FROM_NAME', $local['from_name'] ?? 'CRMS'),
    'contact_recipient' => (string) $env('CRMS_CONTACT_EMAIL', $local['contact_recipient'] ?? 'it.elias38@gmail.com'),
    'timeout' => max(3, min(60, (int) $env('CRMS_MAIL_TIMEOUT', $local['timeout'] ?? 15))),
];
