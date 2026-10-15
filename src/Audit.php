<?php
declare(strict_types=1);

namespace Crms;

final class Audit
{
    public static function log(
        string $action,
        string $entityType,
        ?string $entityId = null,
        ?string $description = null,
        ?array $old = null,
        ?array $new = null,
        string $result = 'success'
    ): void {
        $userId = Auth::id();
        Database::execute(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, description, old_values, new_values, ip_address, user_agent, result)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [
                $userId,
                $action,
                $entityType,
                $entityId,
                $description,
                $old === null ? null : json_encode(self::redact($old)),
                $new === null ? null : json_encode(self::redact($new)),
                Http::ip(),
                Http::userAgent(),
                $result,
            ]
        );
    }

    private static function redact(array $data): array
    {
        $blocked = ['password', 'password_hash', 'token', 'csrf', 'card_number', 'cvv', 'secret'];
        foreach ($data as $k => $v) {
            $lk = strtolower((string) $k);
            foreach ($blocked as $b) {
                if (str_contains($lk, $b)) {
                    $data[$k] = '[redacted]';
                }
            }
            if (is_array($v)) {
                $data[$k] = self::redact($v);
            }
        }
        return $data;
    }
}
