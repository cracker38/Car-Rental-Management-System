<?php
declare(strict_types=1);

namespace Crms;

final class Ui
{
    public static function vehicleImage(?string $path): string
    {
        if ($path && (str_starts_with($path, 'http://') || str_starts_with($path, 'https://'))) {
            return $path;
        }
        if ($path) {
            $local = dirname(__DIR__) . '/' . ltrim(str_replace('\\', '/', $path), '/');
            if (is_file($local)) {
                return asset($path);
            }
        }
        return asset('public/img/fleet/default.svg');
    }

    public static function statusClass(string $status): string
    {
        return match ($status) {
            'available', 'paid', 'completed', 'active', 'confirmed', 'sent' => 'success',
            'pending', 'scheduled', 'draft', 'queued', 'partially_paid' => 'warning',
            'reserved', 'issued', 'inspected' => 'info',
            'rented', 'overdue', 'failed', 'cancelled', 'rejected', 'void', 'blacklisted', 'maintenance', 'inactive' => 'danger',
            default => 'secondary',
        };
    }
}
