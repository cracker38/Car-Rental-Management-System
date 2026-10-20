<?php
declare(strict_types=1);

namespace Crms;

final class Ui
{
    public static function vehicleImage(?string $path, ?string $categoryName = null): string
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
        $category = strtolower(trim($categoryName ?? ''));
        $fallback = match (true) {
            str_contains($category, 'van') => 'van1.jpg',
            str_contains($category, 'luxury') => 'luxury car1.jpg',
            str_contains($category, 'suv'), str_contains($category, '4x4'), str_contains($category, 'crossover') => 'SUV3.jpg',
            str_contains($category, 'sedan') => 'sedan1.jpg',
            str_contains($category, 'economy'), str_contains($category, 'hatchback') => 'hatchback1.jpg',
            default => 'crossover.jpg',
        };
        return asset('public/img/fleet/' . $fallback);
    }

    public static function statusClass(string $status): string
    {
        return match ($status) {
            'available', 'paid', 'Paid', 'completed', 'Completed', 'successful', 'Successful', 'active', 'confirmed', 'sent' => 'success',
            'pending', 'Pending', 'Pending Approval', 'pending approval', 'unpaid', 'Unpaid', 'awaiting_approval', 'scheduled', 'draft', 'queued', 'partially_paid' => 'warning',
            'Approved — Awaiting Payment' => 'info',
            'reserved', 'issued', 'inspected', 'refunded', 'Refunded' => 'info',
            'rented', 'overdue', 'failed', 'cancelled', 'Cancelled', 'rejected', 'Rejected', 'no_show', 'No Show', 'void', 'blacklisted', 'maintenance', 'inactive' => 'danger',
            default => 'secondary',
        };
    }

    public static function reservationStatusLabel(string $status, string $paymentStatus = 'unpaid'): string
    {
        return match ($status) {
            'pending' => 'Pending Approval',
            'confirmed' => $paymentStatus === 'successful' ? 'Paid' : 'Approved — Awaiting Payment',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
            'no_show' => 'No Show',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }
}
