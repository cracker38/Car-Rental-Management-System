<?php
/**
 * HDEV credentials for this XAMPP project.
 * Prefer environment variables in production. Do not put keys in audit logs or Git remotes.
 */
return [
    'payment_api_id' => getenv('HDEV_PAY_API_ID') ?: 'HDEV-3305a9ad-6e9d-4157-92b2-b02573ce84e3-ID',
    'payment_api_key' => getenv('HDEV_PAY_API_KEY') ?: 'HDEV-6e762aab-0c9a-4ac4-bae8-d399b3d157f6-KEY',
    'payment_endpoint' => getenv('HDEV_PAY_ENDPOINT') ?: 'https://payment.hdev.rw/api_pay/api',
    'sms_api_id' => getenv('HDEV_SMS_API_ID') ?: 'HDEV-e6d90ef1-df4c-44cd-8d55-e2c90dcc0893-ID',
    'sms_api_key' => getenv('HDEV_SMS_API_KEY') ?: 'HDEV-e1d9f871-af83-4b39-9989-3530c36a05a6-KEY',
    'sms_endpoint' => getenv('HDEV_SMS_ENDPOINT') ?: 'https://sms-api.hdev.rw/v1/api',
    /**
     * Public HTTPS URL HDEV can call. Localhost is not reachable from HDEV.
     * Example: https://your-ngrok-host/CarrentalMS/hdev_callback.php
     */
    'callback_url' => getenv('HDEV_CALLBACK_URL') ?: '',
    'sms_sender_id' => getenv('HDEV_SMS_SENDER') ?: 'CRMS',
];
