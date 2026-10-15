<?php
declare(strict_types=1);

namespace Crms;

final class Money
{
    public static function round(float|int|string $amount): string
    {
        return number_format(round((float) $amount, 2, PHP_ROUND_HALF_UP), 2, '.', '');
    }

    public static function add(float|int|string $a, float|int|string $b): string
    {
        return self::round((float) $a + (float) $b);
    }

    public static function sub(float|int|string $a, float|int|string $b): string
    {
        return self::round((float) $a - (float) $b);
    }

    public static function mul(float|int|string $a, float|int|string $b): string
    {
        return self::round((float) $a * (float) $b);
    }

    public static function format(float|int|string $amount, string $currency = 'RWF'): string
    {
        return $currency . ' ' . number_format((float) $amount, 0, '.', ',');
    }
}
