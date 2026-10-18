<?php
/**
 * HDEV configuration for this XAMPP project.
 * Set API credentials in the PHP/Apache process environment; keep them out of source files.
 */
return [
    'payment_api_id' => getenv('HDEV_PAY_API_ID') ?: '',
    'payment_api_key' => getenv('HDEV_PAY_API_KEY') ?: '',
    'payment_endpoint' => getenv('HDEV_PAY_ENDPOINT') ?: 'https://payment.hdev.rw/api_pay/api',
    'sms_api_id' => getenv('HDEV_SMS_API_ID') ?: '',
    'sms_api_key' => getenv('HDEV_SMS_API_KEY') ?: '',
    'sms_endpoint' => getenv('HDEV_SMS_ENDPOINT') ?: 'https://sms-api.hdev.rw/v1/api',
    /**
     * Public HTTPS URL HDEV can call. Localhost is not reachable from HDEV.
     * Example: https://your-ngrok-host/CarrentalMS/hdev_callback.php
     */
    'callback_url' => getenv('HDEV_CALLBACK_URL') ?: '',
    'sms_sender_id' => getenv('HDEV_SMS_SENDER') ?: 'CRMS',
];
