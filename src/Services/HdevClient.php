<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Settings;

final class HdevClient
{
    /** @return array<string,string> */
    public static function config(): array
    {
        $file = require dirname(__DIR__, 2) . '/config/hdev.php';
        $callback = Settings::get('hdev_callback_url', (string) ($file['callback_url'] ?? ''));
        $sender = Settings::get('hdev_sms_sender', (string) ($file['sms_sender_id'] ?? 'CRMS'));
        return [
            'payment_api_id' => (string) $file['payment_api_id'],
            'payment_api_key' => (string) $file['payment_api_key'],
            'payment_endpoint' => (string) $file['payment_endpoint'],
            'sms_api_id' => (string) $file['sms_api_id'],
            'sms_api_key' => (string) $file['sms_api_key'],
            'sms_endpoint' => (string) $file['sms_endpoint'],
            'callback_url' => $callback,
            'sms_sender_id' => $sender,
        ];
    }

    public static function paymentReady(): void
    {
        if (!class_exists('hdev_payment', false)) {
            require dirname(__DIR__, 2) . '/lib/hdev/pay_parse.php';
        }
        $c = self::config();
        \hdev_payment::api_id($c['payment_api_id']);
        \hdev_payment::api_key($c['payment_api_key']);
        \hdev_payment::endpoint($c['payment_endpoint']);
    }

    public static function smsReady(): void
    {
        if (!class_exists('hdev_sms', false)) {
            require dirname(__DIR__, 2) . '/lib/hdev/sms_parse.php';
        }
        $c = self::config();
        \hdev_sms::api_id($c['sms_api_id']);
        \hdev_sms::api_key($c['sms_api_key']);
        \hdev_sms::endpoint($c['sms_endpoint']);
    }

    public static function statusOf(object|array|null $result): string
    {
        if ($result === null) {
            return 'error';
        }
        $row = is_array($result) ? $result : (array) $result;
        $status = strtolower((string) ($row['status'] ?? $row['state'] ?? $row['message'] ?? ''));
        if (in_array($status, ['success', 'successful', 'paid', 'completed', 'ok'], true)) {
            return 'success';
        }
        if (in_array($status, ['pending', 'processing', 'initiated'], true)) {
            return 'pending';
        }
        if (in_array($status, ['failed', 'fail', 'error', 'cancelled', 'canceled'], true)) {
            return 'failed';
        }
        return $status !== '' ? $status : 'error';
    }
}
