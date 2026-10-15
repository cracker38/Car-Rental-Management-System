<?php
/**
 * Local XAMPP database settings. Do not commit production secrets.
 * Edit user/password to match your MySQL/MariaDB account.
 */
return [
    'host' => getenv('CRMS_DB_HOST') ?: '127.0.0.1',
    'port' => (int) (getenv('CRMS_DB_PORT') ?: 3306),
    'name' => getenv('CRMS_DB_NAME') ?: 'carrental',
    'user' => getenv('CRMS_DB_USER') ?: 'root',
    'pass' => getenv('CRMS_DB_PASS') !== false ? (string) getenv('CRMS_DB_PASS') : '',
    'charset' => 'utf8mb4',
];
