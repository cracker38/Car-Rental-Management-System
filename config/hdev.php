<?php
$local = [];
$localFile = __DIR__ . '/hdev.local.php';
if (is_file($localFile) && is_readable($localFile)) {
    $loaded = require $localFile;
    $local = is_array($loaded) ? $loaded : [];
}
$env = static function (string $name, string $fallback = ''): string {
    $value = getenv($name);
    return $value !== false && trim($value) !== '' ? $value : $fallback;
};

/**
 * HDEV configuration for this XAMPP project.
 * Environment variables take precedence; hdev.local.php is an ignored local fallback.
 */
return [
    'payment_api_id' => $env('HDEV_PAY_API_ID', (string) ($local['payment_api_id'] ?? '')),
    'payment_api_key' => $env('HDEV_PAY_API_KEY', (string) ($local['payment_api_key'] ?? '')),
    'payment_endpoint' => $env('HDEV_PAY_ENDPOINT', (string) ($local['payment_endpoint'] ?? 'https://payment.hdev.rw/api_pay/api')),
    'sms_api_id' => $env('HDEV_SMS_API_ID', (string) ($local['sms_api_id'] ?? '')),
    'sms_api_key' => $env('HDEV_SMS_API_KEY', (string) ($local['sms_api_key'] ?? '')),
    'sms_endpoint' => $env('HDEV_SMS_ENDPOINT', (string) ($local['sms_endpoint'] ?? 'https://sms-api.hdev.rw/v1/api')),
    /**
     * Public HTTPS URL HDEV can call. Localhost is not reachable from HDEV.
     * Example: https://your-ngrok-host/CarrentalMS/hdev_callback.php
     */
    'callback_url' => $env('HDEV_CALLBACK_URL', (string) ($local['callback_url'] ?? '')),
    'sms_sender_id' => $env('HDEV_SMS_SENDER', (string) ($local['sms_sender_id'] ?? 'CRMS')),
];
