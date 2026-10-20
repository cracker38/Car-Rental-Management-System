<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/src/bootstrap.php';

use Crms\Services\BackupService;

try {
    $retentionValue = getenv('CRMS_BACKUP_RETENTION_DAYS');
    $retentionDays = $retentionValue === false || trim($retentionValue) === ''
        ? 30
        : filter_var($retentionValue, FILTER_VALIDATE_INT);
    if (!is_int($retentionDays)) {
        throw new RuntimeException('CRMS_BACKUP_RETENTION_DAYS must be a whole number of days.');
    }

    $service = new BackupService();
    $backup = $service->exportSql();
    $removed = $service->pruneExpiredBackups($retentionDays);
    fwrite(STDOUT, 'Created ' . basename($backup) . '; removed ' . $removed . ' expired backup(s).' . PHP_EOL);
} catch (Throwable $error) {
    fwrite(STDERR, 'CRMS backup failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
