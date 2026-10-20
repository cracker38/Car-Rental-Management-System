<?php
declare(strict_types=1);

namespace Crms\Services;

final class ReportExportService
{
    /** Keep spreadsheet programs from interpreting untrusted report text as a formula. */
    public static function csvSafeCell(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        if (preg_match('/^[\x00-\x20]*[=+@-]/', $value) === 1) {
            return "'" . $value;
        }

        return $value;
    }
}
