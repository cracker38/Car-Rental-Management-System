<?php
declare(strict_types=1);

namespace Crms;

final class Validator
{
    /** @var array<string,string> */
    public array $errors = [];

    public function require(array $data, array $fields): void
    {
        foreach ($fields as $f) {
            if (!isset($data[$f]) || trim((string) $data[$f]) === '') {
                $this->errors[$f] = 'This field is required.';
            }
        }
    }

    public function email(string $field, string $value): void
    {
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Enter a valid email address.';
        }
    }

    public function in(string $field, string $value, array $allowed): void
    {
        if ($value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field] = 'Invalid value.';
        }
    }

    public function decimal(string $field, string $value, bool $allowEmpty = false): void
    {
        if ($value === '' && $allowEmpty) {
            return;
        }
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
            $this->errors[$field] = 'Enter a valid amount (max 2 decimals).';
        }
    }

    public function ok(): bool
    {
        return $this->errors === [];
    }
}
