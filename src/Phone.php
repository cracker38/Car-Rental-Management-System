<?php
declare(strict_types=1);

namespace Crms;

final class Phone
{
    public static function rwandaMobile(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if (str_starts_with($digits, '250') && strlen($digits) === 12) {
            return $digits;
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '250' . substr($digits, 1);
        }
        if (strlen($digits) === 9 && str_starts_with($digits, '7')) {
            return '250' . $digits;
        }
        return null;
    }

    /** HDEV MoMo/SMS APIs expect a local 07xxxxxxxx number. */
    public static function hdevTel(?string $raw): ?string
    {
        $intl = self::rwandaMobile($raw);
        if ($intl === null) {
            return null;
        }
        return '0' . substr($intl, 3);
    }
}
