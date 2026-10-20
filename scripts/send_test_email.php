<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/src/Services/EmailService.php';

$recipient = trim((string) ($argv[1] ?? ''));
if ($recipient === '') {
    fwrite(STDERR, "Usage: php scripts/send_test_email.php recipient@example.com\n");
    exit(2);
}

try {
    (new Crms\Services\EmailService())->send(
        $recipient,
        'CRMS email delivery test',
        "This is a test message from CRMS.\n\nGmail SMTP accepted the message for delivery."
    );
    fwrite(STDOUT, "Gmail SMTP accepted the test message for {$recipient}. Check the inbox and spam folder.\n");
} catch (Throwable $error) {
    fwrite(STDERR, 'Email test failed: ' . $error->getMessage() . "\n");
    exit(1);
}
