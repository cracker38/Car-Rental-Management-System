<?php
declare(strict_types=1);

namespace Crms;

final class Settings
{
    /** @var array<string,string>|null */
    private static ?array $cache = null;

    public static function get(string $key, string $default = ''): string
    {
        if (self::$cache === null) {
            self::$cache = [];
            try {
                foreach (Database::fetchAll('SELECT setting_key, setting_value FROM system_settings') as $row) {
                    self::$cache[$row['setting_key']] = (string) $row['setting_value'];
                }
            } catch (\Throwable) {
                return $default;
            }
        }
        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, string $value, ?int $userId = null): void
    {
        Database::execute(
            'INSERT INTO system_settings (setting_key, setting_value, updated_by) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)',
            [$key, $value, $userId]
        );
        self::$cache = null;
    }
}
