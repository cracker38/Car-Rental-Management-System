<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/src/bootstrap.php';

use Crms\Services\ReminderService;

$lockPath = dirname(__DIR__) . '/storage/.reminders.lock';
$lock = @fopen($lockPath, 'c');
if ($lock === false) {
    fwrite(STDERR, 'CRMS reminder job could not open its lock file.' . PHP_EOL);
    exit(1);
}
if (!flock($lock, LOCK_EX | LOCK_NB)) {
    fclose($lock);
    fwrite(STDERR, 'Another CRMS reminder job is already running.' . PHP_EOL);
    exit(1);
}

$exitCode = 0;
try {
    $counts = (new ReminderService())->sendDueReminders();
    fwrite(STDOUT, sprintf(
        'Created %d return, %d overdue-return, and %d payment reminder(s).%s',
        $counts['returns'],
        $counts['overdue_returns'],
        $counts['payments'],
        PHP_EOL
    ));
} catch (Throwable $error) {
    fwrite(STDERR, 'CRMS reminder job failed: ' . $error->getMessage() . PHP_EOL);
    $exitCode = 1;
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
exit($exitCode);
