<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Settings;
use InvalidArgumentException;

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
        $c = self::config();
        if (trim($c['payment_api_id']) === '' || trim($c['payment_api_key']) === '') {
            throw new InvalidArgumentException('HDEV payment credentials are not configured in the server environment.');
        }
        if (!class_exists('hdev_payment', false)) {
            require dirname(__DIR__, 2) . '/lib/hdev/pay_parse.php';
        }
        \hdev_payment::api_id($c['payment_api_id']);
        \hdev_payment::api_key($c['payment_api_key']);
        \hdev_payment::endpoint($c['payment_endpoint']);
    }

    public static function smsReady(): void
    {
        $c = self::config();
        if (trim($c['sms_api_id']) === '' || trim($c['sms_api_key']) === '') {
            throw new InvalidArgumentException('HDEV SMS credentials are not configured in the server environment.');
        }
        if (!class_exists('hdev_sms', false)) {
            require dirname(__DIR__, 2) . '/lib/hdev/sms_parse.php';
        }
        \hdev_sms::api_id($c['sms_api_id']);
        \hdev_sms::api_key($c['sms_api_key']);
        \hdev_sms::endpoint($c['sms_endpoint']);
    }

    public static function payload(object|array|null $result): array
    {
        if ($result === null) {
            return [];
        }
        $row = is_array($result) ? $result : (array) $result;
        $outerStatus = $row['status'] ?? null;
        if (isset($row['data']) && (is_array($row['data']) || is_object($row['data']))) {
            $data = (array) $row['data'];
            unset($row['data']);
            $row = array_merge($row, $data);
            if (is_string($outerStatus) && $outerStatus !== '') {
                $row['status'] = $outerStatus;
            }
        }
        return $row;
    }

    public static function statusOf(object|array|null $result): string
    {
        $row = self::payload($result);
        $status = strtolower(trim((string) ($row['status'] ?? $row['state'] ?? $row['payment_status'] ?? $row['transaction_status'] ?? '')));
        if (in_array($status, ['success', 'successful', 'paid', 'completed', 'ok', '1', 'true'], true)) {
            return 'success';
        }
        if (in_array($status, ['pending', 'processing', 'initiated', 'queued'], true)) {
            return 'pending';
        }
        if (in_array($status, ['failed', 'fail', 'error', 'cancelled', 'canceled', 'rejected', '0', 'false'], true)) {
            return 'failed';
        }
        return $status !== '' ? $status : 'error';
    }

    public static function messageOf(object|array|null $result): string
    {
        $row = self::payload($result);
        $message = trim((string) ($row['message'] ?? $row['msg'] ?? $row['error'] ?? $row['description'] ?? ''));
        return $message;
    }

    public static function accepted(object|array|null $result): bool
    {
        return in_array(self::statusOf($result), ['success', 'pending'], true);
    }

    public static function remoteAmount(object|array|null $result): ?string
    {
        $row = self::payload($result);
        if (!isset($row['amount']) && !isset($row['paid_amount'])) {
            return null;
        }
        $raw = $row['amount'] ?? $row['paid_amount'];
        return \Crms\Money::round($raw);
    }
}
