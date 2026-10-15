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
        $tel = Phone::rwandaMobile($customer['phone'] ?? null);
        $enabled = Settings::get('hdev_sms_enabled', 'true') === 'true';
        $status = 'queued';
        $error = null;
        $sentAt = null;
        if ($enabled && $tel) {
            try {
                HdevClient::smsReady();
                $cfg = HdevClient::config();
                $result = \hdev_sms::send($cfg['sms_sender_id'], $tel, $message);
                $st = HdevClient::statusOf($result);
                if ($st === 'success' || $st === 'pending' || $st === 'ok') {
                    $status = 'sent';
                    $sentAt = date('Y-m-d H:i:s');
                } else {
                    $status = 'failed';
                    $error = substr((string) (is_object($result) ? ($result->message ?? $result->status ?? 'SMS rejected') : 'SMS rejected'), 0, 500);
                }
            } catch (Throwable $e) {
                $status = 'failed';
                $error = 'SMS gateway error';
            }
        } elseif (!$tel) {
            $status = 'failed';
            $error = 'Customer phone is not a valid Rwanda mobile number.';
        }

        Database::execute(
            'INSERT INTO notifications (user_id, customer_id, channel, notification_type, subject, message, related_entity_type, related_entity_id, status, sent_at, error_message)
             VALUES (?,?,\'sms\',?,?,?,?,?,?,?,?)',
            [
                $customer['linked_user_id'] ?? null, $customerId, $type, $subject, $message,
                $entity, $entityId, $status, $sentAt, $error,
            ]
        );
        Audit::log('sms.' . $status, 'notification', $entityId ? (string) $entityId : null, $type . ' to customer ' . $customerId);
    }
}
