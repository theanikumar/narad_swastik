<?php

declare(strict_types=1);

namespace App\Helpers;

final class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, ?string $label = null): self
    {
        $label = $label ?? str_replace('_', ' ', $field);
        if (!isset($this->data[$field]) || (is_string($this->data[$field]) && trim($this->data[$field]) === '')) {
            $this->errors[$field][] = "{$label} is required";
        }
        return $this;
    }

    public function email(string $field, ?string $label = null): self
    {
        $label = $label ?? str_replace('_', ' ', $field);
        if (isset($this->data[$field]) && is_string($this->data[$field]) && trim($this->data[$field]) !== '') {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field][] = "{$label} must be a valid email address";
            }
        }
        return $this;
    }

    public function minLength(string $field, int $length, ?string $label = null): self
    {
        $label = $label ?? str_replace('_', ' ', $field);
        if (isset($this->data[$field]) && is_string($this->data[$field]) && strlen(trim($this->data[$field])) < $length) {
            $this->errors[$field][] = "{$label} must be at least {$length} characters";
        }
        return $this;
    }

    public function maxLength(string $field, int $length, ?string $label = null): self
    {
        $label = $label ?? str_replace('_', ' ', $field);
        if (isset($this->data[$field]) && is_string($this->data[$field]) && strlen(trim($this->data[$field])) > $length) {
            $this->errors[$field][] = "{$label} must not exceed {$length} characters";
        }
        return $this;
    }

    public function inArray(string $field, array $values, ?string $label = null): self
    {
        $label = $label ?? str_replace('_', ' ', $field);
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values, true)) {
            $this->errors[$field][] = "{$label} must be one of: " . implode(', ', $values);
        }
        return $this;
    }

    public function integer(string $field, ?string $label = null): self
    {
        $label = $label ?? str_replace('_', ' ', $field);
        if (isset($this->data[$field]) && $this->data[$field] !== '' && $this->data[$field] !== null) {
            $value = $this->data[$field];
            if (is_string($value)) {
                $value = trim($value);
            }
            if (!filter_var($value, FILTER_VALIDATE_INT)) {
                $this->errors[$field][] = "{$label} must be an integer";
            }
        }
        return $this;
    }

    public function numeric(string $field, ?string $label = null): self
    {
        $label = $label ?? str_replace('_', ' ', $field);
        if (isset($this->data[$field]) && $this->data[$field] !== '' && $this->data[$field] !== null) {
            if (!is_numeric($this->data[$field])) {
                $this->errors[$field][] = "{$label} must be a number";
            }
        }
        return $this;
    }

    public function date(string $field, ?string $label = null): self
    {
        $label = $label ?? str_replace('_', ' ', $field);
        if (isset($this->data[$field]) && $this->data[$field] !== '' && $this->data[$field] !== null) {
            if (!strtotime((string)$this->data[$field])) {
                $this->errors[$field][] = "{$label} must be a valid date";
            }
        }
        return $this;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        return $this->data;
    }

    public static function sanitize(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = strip_tags($value);
            $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $value = trim($value);
            return $value === '' ? null : $value;
        }
        if (is_array($value)) {
            return array_map([self::class, 'sanitize'], $value);
        }
        return $value;
    }

    public function applySanitization(array $fields): self
    {
        foreach ($fields as $field) {
            if (isset($this->data[$field])) {
                $this->data[$field] = self::sanitize($this->data[$field]);
            }
        }
        return $this;
    }
}
