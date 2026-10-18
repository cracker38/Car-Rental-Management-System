<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Auth;
use Crms\Database;

final class DriverAccess
{
    public static function scopeSql(string $alias = 'd'): string
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/i', $alias)) {
            throw new \InvalidArgumentException('Invalid driver query alias.');
        }
        if ((Auth::user()['role_name'] ?? '') !== 'Business Manager') {
            return '1=1';
        }
        return "NOT EXISTS (
            SELECT 1
            FROM users AS driver_creator
            JOIN roles AS driver_creator_role ON driver_creator_role.role_id = driver_creator.role_id
            WHERE driver_creator.user_id = {$alias}.created_by
              AND driver_creator_role.role_name = 'System Administrator'
        )";
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
