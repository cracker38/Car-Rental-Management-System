<?php
declare(strict_types=1);

namespace Crms\Services;

use InvalidArgumentException;

final class PawaPayClient
{
    /** @return array<string,mixed> */
    public static function config(): array
    {
        $config = require dirname(__DIR__, 2) . '/config/pawapay.php';
        return [
            'base_url' => trim((string) ($config['base_url'] ?? '')),
            'api_token' => trim((string) ($config['api_token'] ?? '')),
        ];
    }

    public static function assertReady(): void
    {
        $config = self::config();
        if ($config['api_token'] === '') {
            throw new InvalidArgumentException('PawaPay is not configured. Set PAWAPAY_API_TOKEN on the server.');
        }
        if (!preg_match('#^https://[^/]+$#i', $config['base_url'])) {
            throw new InvalidArgumentException('PAWAPAY_BASE_URL must be an HTTPS origin without a path.');
        }
        if (!function_exists('curl_init')) {
            throw new InvalidArgumentException('The PHP cURL extension is required for PawaPay payments.');
        }
    }

    /** @return array<string,mixed> */
    public function createDeposit(array $payload): array
    {
        return $this->request('POST', '/v2/deposits', $payload);
    }

    /** @return array<string,mixed> */
    public function getDeposit(string $depositId): array
    {
        return $this->request('GET', '/v2/deposits/' . rawurlencode($depositId));
    }

    /** @return array<string,mixed> */
    private function request(string $method, string $path, ?array $payload = null): array
    {
        self::assertReady();
        $config = self::config();
        $curl = curl_init($config['base_url'] . $path);
        if ($curl === false) {
            return ['transport_error' => true, 'message' => 'Could not initialize the PawaPay connection.'];
        }
        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . $config['api_token'],
        ];
        if ($payload !== null) {
            $headers[] = 'Content-Type: application/json';
        }
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        if ($payload !== null) {
            $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $options[CURLOPT_POSTFIELDS] = $encoded;
        }
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        $curlErrno = curl_errno($curl);
        curl_close($curl);

        if ($response === false) {
            return [
                'transport_error' => true,
                'message' => $curlError !== '' ? $curlError : 'PawaPay did not return a response.',
                'curl_errno' => $curlErrno,
                'http_status' => $httpStatus,
            ];
        }
        $decoded = json_decode((string) $response, true);
        if (!is_array($decoded)) {
            return [
                'transport_error' => $httpStatus >= 500,
                'message' => 'PawaPay returned an unreadable response.',
                'http_status' => $httpStatus,
            ];
        }
        $decoded['http_status'] = $httpStatus;
        return $decoded;
    }

    /** @param array<string,mixed> $response */
    public static function failureMessage(array $response): string
    {
        $reason = $response['failureReason'] ?? $response['rejectionReason'] ?? null;
        if (is_array($reason)) {
            $message = trim((string) ($reason['failureMessage'] ?? $reason['rejectionMessage'] ?? $reason['message'] ?? ''));
            $code = trim((string) ($reason['failureCode'] ?? $reason['rejectionCode'] ?? $reason['code'] ?? ''));
            if ($message !== '' && $code !== '') {
                return $code . ': ' . $message;
            }
            if ($message !== '' || $code !== '') {
                return $message !== '' ? $message : $code;
            }
        }
        foreach (['errorMessage', 'message', 'detail', 'error'] as $key) {
            if (isset($response[$key]) && is_string($response[$key]) && trim($response[$key]) !== '') {
                return trim($response[$key]);
            }
        }
        $httpStatus = (int) ($response['http_status'] ?? 0);
        return $httpStatus > 0 && $httpStatus >= 400 ? 'PawaPay returned HTTP ' . $httpStatus . '.' : '';
    }
}
