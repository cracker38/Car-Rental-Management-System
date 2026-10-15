<?php
/**
 * HDEV Payment Gateway client (readable MIT-style wrapper).
 * Matches hdev_payment::api_id / api_key / pay / get_pay.
 * Official GitHub pay_parse.php is obfuscated eval; this project uses a clear cURL client instead.
 */
class hdev_payment
{
    private static string $api_id = '';
    private static string $api_key = '';
    private static string $endpoint = 'https://payment.hdev.rw/api_pay/api';

    public static function api_id($api_id): void
    {
        self::$api_id = (string) $api_id;
    }

    public static function api_key($api_key): void
    {
        self::$api_key = (string) $api_key;
    }

    public static function endpoint($endpoint): void
    {
        self::$endpoint = rtrim((string) $endpoint, '/');
    }

    public static function pay($tel, $amount, $transaction_ref, $calback)
    {
        return self::request([
            'ref' => 'pay',
            'tel' => $tel,
            'amount' => $amount,
            'tx_ref' => $transaction_ref,
            'link' => $calback,
        ]);
    }

    public static function get_pay($tx_ref)
    {
        return self::request([
            'ref' => 'read',
            'tx_ref' => $tx_ref,
        ]);
    }

    private static function request(array $fields)
    {
        if (self::$api_id === '' || self::$api_key === '') {
            return (object) ['status' => 'error', 'message' => 'HDEV payment API credentials are missing.'];
        }
        $url = self::$endpoint . '/' . rawurlencode(self::$api_id) . '/' . rawurlencode(self::$api_key);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $fields,
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($response === false) {
            return (object) ['status' => 'error', 'message' => $err !== '' ? $err : 'HDEV payment request failed.'];
        }
        $decoded = json_decode($response);
        if (is_object($decoded) || is_array($decoded)) {
            return $decoded;
        }
        return (object) ['status' => 'error', 'message' => 'Unexpected HDEV payment response.', 'raw' => $response];
    }
}
