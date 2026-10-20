<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Database;

final class DriverAccess
{
    public static function scopeSql(string $alias = 'd'): string
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/i', $alias)) {
            throw new \InvalidArgumentException('Invalid driver query alias.');
        }
        // drivers.manage is role-scoped. Managers intentionally see and manage drivers
        // created by the System Administrator as well as their own driver records.
        return '1=1';
    }

    public static function canAccess(int $driverId): bool
    {
        if ($driverId <= 0) {
            return false;
        }
        return Database::fetch(
            'SELECT d.driver_id FROM drivers d WHERE d.driver_id = ? AND ' . self::scopeSql('d'),
            [$driverId]
        ) !== null;
    }
}
