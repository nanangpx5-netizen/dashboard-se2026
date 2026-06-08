<?php

declare(strict_types=1);

namespace App\Helpers;

final class Validator
{
    private array $errors = [];

    public static function validateStatic(array $data, array $rules): array
    {
        $instance = new self();
        return $instance->validate($data, $rules);
    }

    public static function validateOrFailStatic(array $data, array $rules): array
    {
        $instance = new self();
        $result = $instance->validate($data, $rules);
        if ($instance->hasErrors()) {
            throw new ValidationException($instance->errors(), 'Validasi gagal');
        }
        return $result;
    }

    public function validate(array $data, array $rules): array
    {
        $cleaned = [];
        $this->errors = [];

        foreach ($rules as $field => $ruleSet) {
            $value = $data[$field] ?? null;
            $rules = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;

            foreach ($rules as $rule) {
                $params = [];

                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                $result = match ($rule) {
                    'required' => $this->validateRequired($field, $value),
                    'string' => $this->validateString($field, $value),
                    'numeric' => $this->validateNumeric($field, $value),
                    'int' => $this->validateInt($field, $value),
                    'email' => $this->validateEmail($field, $value),
                    'regex' => $this->validateRegex($field, $value, $params[0] ?? null),
                    'in', 'in_array' => $this->validateInArray($field, $value, $params),
                    'min' => $this->validateMin($field, $value, (int) ($params[0] ?? 0)),
                    'max' => $this->validateMax($field, $value, (int) ($params[0] ?? 0)),
                    'alpha' => $this->validateAlpha($field, $value),
                    'alphanum' => $this->validateAlphaNum($field, $value),
                    'bool' => $this->validateBool($field, $value),
                    'trim' => $this->sanitizeTrim($field, $value),
                    'strip_tags' => $this->sanitizeStripTags($field, $value),
                    default => null,
                };

                if ($result === false) {
                    break;
                }

                if (is_array($result)) {
                    $data[$field] = $value = $result['value'];
                }
            }

            if (!isset($this->errors[$field])) {
                $cleaned[$field] = $value;
            }
        }

        return $cleaned;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function firstError(): string
    {
        return $this->errors ? reset($this->errors) : '';
    }

    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    private function validateRequired(string $field, mixed $value): bool
    {
        if ($value === null || $value === '' || $value === []) {
            $this->addError($field, "Field '$field' wajib diisi.");
            return false;
        }
        return true;
    }

    private function validateString(string $field, mixed $value): bool
    {
        if ($value !== null && $value !== '' && !is_string($value)) {
            $this->addError($field, "Field '$field' harus berupa string.");
            return false;
        }
        return true;
    }

    private function validateNumeric(string $field, mixed $value): bool
    {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->addError($field, "Field '$field' harus berupa angka.");
            return false;
        }
        return true;
    }

    private function validateInt(string $field, mixed $value): bool|array
    {
        if ($value === null || $value === '') {
            return true;
        }
        $intVal = filter_var($value, FILTER_VALIDATE_INT);
        if ($intVal === false) {
            $this->addError($field, "Field '$field' harus berupa bilangan bulat.");
            return false;
        }
        return ['value' => $intVal];
    }

    private function validateEmail(string $field, mixed $value): bool
    {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "Field '$field' harus berupa email valid.");
            return false;
        }
        return true;
    }

    private function validateRegex(string $field, mixed $value, ?string $pattern): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if ($pattern === null || preg_match($pattern, (string) $value) !== 1) {
            $this->addError($field, "Field '$field' tidak valid.");
            return false;
        }
        return true;
    }

    private function validateInArray(string $field, mixed $value, array $allowed): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!in_array((string) $value, $allowed, true)) {
            $this->addError($field, "Field '$field' tidak valid. Pilihan: " . implode(', ', $allowed));
            return false;
        }
        return true;
    }

    private function validateMin(string $field, mixed $value, int $min): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $len = is_string($value) ? strlen($value) : (is_numeric($value) ? (int) $value : 0);
        if ($len < $min) {
            $this->addError($field, "Field '$field' minimal $min.");
            return false;
        }
        return true;
    }

    private function validateMax(string $field, mixed $value, int $max): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $len = is_string($value) ? strlen($value) : (is_numeric($value) ? (int) $value : PHP_INT_MAX);
        if ($len > $max) {
            $this->addError($field, "Field '$field' maksimal $max.");
            return false;
        }
        return true;
    }

    private function validateAlpha(string $field, mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!ctype_alpha((string) $value)) {
            $this->addError($field, "Field '$field' hanya boleh huruf.");
            return false;
        }
        return true;
    }

    private function validateAlphaNum(string $field, mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!ctype_alnum((string) $value)) {
            $this->addError($field, "Field '$field' hanya boleh huruf dan angka.");
            return false;
        }
        return true;
    }

    private function validateBool(string $field, mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $allowed = [true, false, 1, 0, '1', '0', 'true', 'false', 'on', 'off', 'yes', 'no'];
        if (!in_array($value, $allowed, true)) {
            $this->addError($field, "Field '$field' harus boolean.");
            return false;
        }
        return true;
    }

    private function sanitizeTrim(string $field, mixed &$value): ?array
    {
        if (is_string($value)) {
            $value = trim($value);
            return ['value' => $value];
        }
        return null;
    }

    private function sanitizeStripTags(string $field, mixed &$value): ?array
    {
        if (is_string($value)) {
            $value = strip_tags($value);
            return ['value' => $value];
        }
        return null;
    }
}
