<?php
declare(strict_types=1);

namespace Crms;

use DateTimeImmutable;

final class Period
{
    public static function overlaps(string $existStart, string $existEnd, string $reqStart, string $reqEnd, int $bufferMinutes = 0): bool
    {
        $es = new DateTimeImmutable($existStart);
        $ee = (new DateTimeImmutable($existEnd))->modify('+' . $bufferMinutes . ' minutes');
        $rs = new DateTimeImmutable($reqStart);
        $re = (new DateTimeImmutable($reqEnd))->modify('+' . $bufferMinutes . ' minutes');
        return $es < $re && $ee > $rs;
    }
}
