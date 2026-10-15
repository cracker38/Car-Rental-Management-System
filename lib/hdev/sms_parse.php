<?php
/**
 * HDEV SMS Gateway client matching hdev_sms::api_id / api_key / send / topup.
 */
class hdev_sms
{
    private static string $api_id = '';
    private static string $api_key = '';
    private static string $endpoint = 'https://sms-api.hdev.rw/v1/api';

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

    public static function send($sender_id, $tel, $message)
    {
        return self::request([
            'sender_id' => $sender_id,
            'ref' => 'sms',
            'message' => $message,
            'tel' => $tel,
        ]);
    }

    public static function topup($tel, $amount)
    {
        return self::request([
            'ref' => 'topup',
            'tel' => $tel,
            'amount' => $amount,
        ]);
    }

    private static function request(array $data)
    {
        if (self::$api_id === '' || self::$api_key === '') {
            return (object) ['status' => 'error', 'message' => 'HDEV SMS API credentials are missing.'];
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
            CURLOPT_POSTFIELDS => $data,
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($response === false) {
            return (object) ['status' => 'error', 'message' => $err !== '' ? $err : 'HDEV SMS request failed.'];
        }
        $decoded = json_decode($response);
        if (is_object($decoded) || is_array($decoded)) {
            return $decoded;
        }
        return (object) ['status' => 'error', 'message' => 'Unexpected HDEV SMS response.', 'raw' => $response];
    }
}
