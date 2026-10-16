<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Audit;
use Crms\Database;
use Crms\Phone;
use Crms\Settings;
use Throwable;

final class SmsService
{
    public function sendToCustomer(?int $customerId, string $type, string $subject, string $message, ?string $entity = null, ?int $entityId = null): void
    {
        if (!$customerId) {
            return;
        }
        $customer = Database::fetch('SELECT phone, linked_user_id FROM customers WHERE customer_id = ?', [$customerId]);
        if (!$customer) {
            return;
        }
        $result = $this->deliver((string) ($customer['phone'] ?? ''), $message);
        Database::execute(
            'INSERT INTO notifications (user_id, customer_id, channel, notification_type, subject, message, related_entity_type, related_entity_id, status, sent_at, error_message)
             VALUES (?,?,\'sms\',?,?,?,?,?,?,?,?)',
            [
                $customer['linked_user_id'] ?? null, $customerId, $type, $subject, $message,
                $entity, $entityId, $result['status'], $result['sent_at'], $result['error'],
            ]
        );
        Audit::log('sms.' . $result['status'], 'notification', $entityId ? (string) $entityId : null, $type . ' to customer ' . $customerId);
    }

    /** @return array{status:string,sent_at:?string,error:?string} */
    public function deliver(string $phone, string $message): array
    {
        $tel = Phone::hdevTel($phone);
        $enabled = Settings::get('hdev_sms_enabled', 'true') === 'true';
        if (!$enabled) {
            return ['status' => 'queued', 'sent_at' => null, 'error' => 'SMS is disabled in settings.'];
        }
        if (!$tel) {
            return ['status' => 'failed', 'sent_at' => null, 'error' => 'Phone is not a valid Rwanda mobile number.'];
        }
        try {
            HdevClient::smsReady();
            $cfg = HdevClient::config();
            $result = \hdev_sms::send($cfg['sms_sender_id'], $tel, $message);
            $st = HdevClient::statusOf($result);
            if (in_array($st, ['success', 'pending'], true)) {
                return ['status' => 'sent', 'sent_at' => date('Y-m-d H:i:s'), 'error' => null];
            }
            $error = HdevClient::messageOf($result);
            return ['status' => 'failed', 'sent_at' => null, 'error' => substr($error !== '' ? $error : 'SMS rejected by HDEV', 0, 500)];
        } catch (Throwable $e) {
            return ['status' => 'failed', 'sent_at' => null, 'error' => substr($e->getMessage(), 0, 500)];
        }
    }
}
