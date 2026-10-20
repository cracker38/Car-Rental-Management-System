<?php
declare(strict_types=1);

namespace Crms\Services;

use RuntimeException;
use Throwable;

final class EmailService
{
    /** @var array<string,mixed>|null */
    private ?array $config = null;

    public function send(string $recipient, string $subject, string $message): void
    {
        $config = $this->config();
        $recipient = trim($recipient);
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('The recipient email address is missing or invalid.');
        }
        if (!filter_var((string) $config['from_address'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Set a valid CRMS_MAIL_FROM_ADDRESS or Gmail username.');
        }
        if ((string) $config['username'] === '' || (string) $config['password'] === '') {
            throw new RuntimeException('Gmail SMTP is not configured. Set the Gmail username and Google app password.');
        }

        $host = trim((string) $config['host']);
        $port = (int) $config['port'];
        $encryption = strtolower((string) $config['encryption']);
        if ($host === '' || $port < 1 || $port > 65535 || !in_array($encryption, ['tls', 'ssl'], true)) {
            throw new RuntimeException('Gmail SMTP host, port, or encryption settings are invalid.');
        }

        $remote = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'peer_name' => $host,
                'SNI_enabled' => true,
            ],
        ]);
        $socket = @stream_socket_client(
            $remote,
            $errorCode,
            $errorMessage,
            (int) $config['timeout'],
            STREAM_CLIENT_CONNECT,
            $context
        );
        if (!is_resource($socket)) {
            throw new RuntimeException('Could not connect to Gmail SMTP (' . (int) $errorCode . '): ' . $this->safeText((string) $errorMessage));
        }

        stream_set_timeout($socket, (int) $config['timeout']);
        try {
            $this->expect($socket, [220], 'server greeting');
            $this->command($socket, 'EHLO ' . $this->clientName(), [250]);
            if ($encryption === 'tls') {
                $this->command($socket, 'STARTTLS', [220]);
                $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($crypto !== true) {
                    throw new RuntimeException('Could not establish a verified TLS connection to Gmail SMTP.');
                }
                $this->command($socket, 'EHLO ' . $this->clientName(), [250]);
            }

            $this->command($socket, 'AUTH LOGIN', [334]);
            $this->command($socket, base64_encode((string) $config['username']), [334]);
            $this->command($socket, base64_encode((string) $config['password']), [235]);
            $this->command($socket, 'MAIL FROM:<' . (string) $config['from_address'] . '>', [250]);
            $this->command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
            $this->command($socket, 'DATA', [354]);

            $data = $this->buildMessage($recipient, $subject, $message, $config);
            $this->writeAll($socket, $this->dotStuff($data) . "\r\n.\r\n");
            $this->expect($socket, [250], 'message acceptance');
            $this->command($socket, 'QUIT', [221]);
        } catch (Throwable $error) {
            throw $error instanceof RuntimeException
                ? $error
                : new RuntimeException('Gmail SMTP send failed: ' . $this->safeText($error->getMessage()), 0, $error);
        } finally {
            if (is_resource($socket)) {
                fclose($socket);
            }
        }
    }

    /** @return array<string,mixed> */
    private function config(): array
    {
        if ($this->config === null) {
            $loaded = require dirname(__DIR__, 2) . '/config/mail.php';
            $this->config = is_array($loaded) ? $loaded : [];
        }
        return $this->config;
    }

    /** @param resource $socket @param list<int> $expected */
    private function command($socket, string $command, array $expected): string
    {
        $this->writeAll($socket, $command . "\r\n");
        return $this->expect($socket, $expected, 'command');
    }

    /** @param resource $socket */
    private function writeAll($socket, string $data): void
    {
        $length = strlen($data);
        $offset = 0;
        while ($offset < $length) {
            $written = @fwrite($socket, substr($data, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('Could not write to the Gmail SMTP connection.');
            }
            $offset += $written;
        }
    }

    /** @param resource $socket @param list<int> $expected */
    private function expect($socket, array $expected, string $operation): string
    {
        $lines = [];
        $code = 0;
        for ($i = 0; $i < 50; $i++) {
            $line = @fgets($socket, 2048);
            if ($line === false) {
                $meta = stream_get_meta_data($socket);
                $reason = !empty($meta['timed_out']) ? 'timed out' : 'closed the connection';
                throw new RuntimeException('Gmail SMTP ' . $reason . ' during ' . $operation . '.');
            }
            $lines[] = trim($line);
            if (preg_match('/^(\d{3})([ -])/', $line, $matches) !== 1) {
                throw new RuntimeException('Gmail SMTP returned an invalid response during ' . $operation . '.');
            }
            $code = (int) $matches[1];
            if ($matches[2] === ' ') {
                break;
            }
        }
        if ($lines === [] || preg_match('/^\d{3} /', end($lines)) !== 1) {
            throw new RuntimeException('Gmail SMTP returned an incomplete response during ' . $operation . '.');
        }
        if (!in_array($code, $expected, true)) {
            throw new RuntimeException('Gmail SMTP rejected ' . $operation . ' (' . $code . '): ' . $this->safeText(implode(' ', $lines)));
        }
        return implode("\n", $lines);
    }

    /** @param array<string,mixed> $config */
    private function buildMessage(string $recipient, string $subject, string $message, array $config): string
    {
        $fromName = $this->encodeHeader($this->safeHeader((string) $config['from_name']));
        $subject = $this->encodeHeader($this->safeHeader($subject));
        $body = str_replace(["\r\n", "\r"], "\n", $message);
        $body = str_replace("\n", "\r\n", quoted_printable_encode($body));
        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . ($fromName !== '' ? $fromName . ' ' : '') . '<' . (string) $config['from_address'] . '>',
            'To: <' . $recipient . '>',
            'Subject: ' . $subject,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
            'X-Mailer: CRMS',
        ];
        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    private function encodeHeader(string $value): string
    {
        if ($value === '') {
            return '';
        }
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function safeHeader(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }

    private function dotStuff(string $data): string
    {
        return preg_replace('/(^|\r\n)\./', '$1..', $data) ?? $data;
    }

    private function clientName(): string
    {
        $name = gethostname();
        return is_string($name) && preg_match('/^[A-Za-z0-9.-]{1,200}$/', $name) === 1 ? $name : 'localhost';
    }

    private function safeText(string $text): string
    {
        $text = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $text) ?? '';
        return trim(substr($text, 0, 300));
    }
}
