<?php
declare(strict_types=1);

namespace Crms;

use InvalidArgumentException;

final class Signature
{
    public static function decodePng(string $dataUri): string
    {
        if (!preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/D', trim($dataUri), $match)) {
            throw new InvalidArgumentException('Draw and save the required signature before submitting.');
        }
        $binary = base64_decode($match[1], true);
        if ($binary === false || strlen($binary) < 100 || strlen($binary) > 500000) {
            throw new InvalidArgumentException('The signature image is invalid or too large. Please sign again.');
        }
        $image = @getimagesizefromstring($binary);
        if (!$image || ($image['mime'] ?? '') !== 'image/png'
            || (int) $image[0] < 100 || (int) $image[1] < 20
            || (int) $image[0] > 1200 || (int) $image[1] > 500) {
            throw new InvalidArgumentException('The signature image must be a valid PNG up to 1200 by 500 pixels.');
        }
        if (!self::containsInk($binary, (int) $image[0], (int) $image[1])) {
            throw new InvalidArgumentException('The signature image is blank. Draw your signature and try again.');
        }
        return $binary;
    }

    public static function dataUri(?string $binary): ?string
    {
        return $binary === null || $binary === '' ? null : 'data:image/png;base64,' . base64_encode($binary);
    }

    private static function containsInk(string $png, int $width, int $height): bool
    {
        if (!function_exists('zlib_decode') || strlen($png) < 33 || substr($png, 12, 4) !== 'IHDR') {
            return false;
        }
        $header = unpack('Cdepth/CcolorType', substr($png, 24, 2));
        $depth = (int) ($header['depth'] ?? 0);
        $colorType = (int) ($header['colorType'] ?? 0);
        if ($depth !== 8 || !in_array($colorType, [2, 6], true)) {
            return false;
        }
        $bytesPerPixel = $colorType === 6 ? 4 : 3;
        $compressed = '';
        $offset = 8;
        $pngLength = strlen($png);
        while ($offset + 12 <= $pngLength) {
            $chunkLength = unpack('Nlength', substr($png, $offset, 4));
            $length = (int) ($chunkLength['length'] ?? 0);
            $type = substr($png, $offset + 4, 4);
            if ($length < 0 || $offset + 12 + $length > $pngLength) {
                return false;
            }
            if ($type === 'IDAT') {
                $compressed .= substr($png, $offset + 8, $length);
                if (strlen($compressed) > 500000) {
                    return false;
                }
            }
            $offset += 12 + $length;
            if ($type === 'IEND') {
                break;
            }
        }
        if ($compressed === '') {
            return false;
        }
        $rowBytes = $width * $bytesPerPixel;
        $raw = @zlib_decode($compressed, ($rowBytes + 1) * $height);
        if ($raw === false || strlen($raw) !== ($rowBytes + 1) * $height) {
            return false;
        }

        $previous = array_fill(0, $rowBytes, 0);
        $inkPixels = 0;
        $minX = $width;
        $maxX = -1;
        $minY = $height;
        $maxY = -1;
        $cursor = 0;
        for ($y = 0; $y < $height; $y++) {
            $filter = ord($raw[$cursor++]);
            if ($filter > 4) {
                return false;
            }
            $row = [];
            for ($x = 0; $x < $rowBytes; $x++) {
                $value = ord($raw[$cursor++]);
                $left = $x >= $bytesPerPixel ? $row[$x - $bytesPerPixel] : 0;
                $above = $previous[$x];
                $upperLeft = $x >= $bytesPerPixel ? $previous[$x - $bytesPerPixel] : 0;
                $predictor = match ($filter) {
                    1 => $left,
                    2 => $above,
                    3 => intdiv($left + $above, 2),
                    4 => self::paeth($left, $above, $upperLeft),
                    default => 0,
                };
                $row[$x] = ($value + $predictor) & 255;
            }
            for ($x = 0; $x < $width; $x++) {
                $pixel = $x * $bytesPerPixel;
                $red = $row[$pixel];
                $green = $row[$pixel + 1];
                $blue = $row[$pixel + 2];
                $alpha = $colorType === 6 ? $row[$pixel + 3] : 255;
                if ($alpha > 24 && ($red < 235 || $green < 235 || $blue < 235)) {
                    $inkPixels++;
                    $minX = min($minX, $x);
                    $maxX = max($maxX, $x);
                    $minY = min($minY, $y);
                    $maxY = max($maxY, $y);
                }
            }
            $previous = $row;
        }
        return $inkPixels >= 12 && ($maxX - $minX) >= 3 && ($maxY - $minY) >= 3;
    }

    private static function paeth(int $left, int $above, int $upperLeft): int
    {
        $base = $left + $above - $upperLeft;
        $leftDistance = abs($base - $left);
        $aboveDistance = abs($base - $above);
        $upperLeftDistance = abs($base - $upperLeft);
        if ($leftDistance <= $aboveDistance && $leftDistance <= $upperLeftDistance) {
            return $left;
        }
        if ($aboveDistance <= $upperLeftDistance) {
            return $above;
        }
        return $upperLeft;
    }
}
