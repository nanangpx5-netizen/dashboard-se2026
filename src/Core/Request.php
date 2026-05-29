<?php

namespace App\Core;

final class Request
{
    private array $query;
    private array $body;
    private array $files;
    private array $server;

    public function __construct()
    {
        $this->query  = $_GET;
        $this->body   = $_POST;
        $this->files  = $_FILES;
        $this->server = $_SERVER;
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function isAjax(): bool
    {
        return (strtoupper($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
            || ($this->input('ajax') === '1')
            || ($this->input('ajax') === 'true');
    }

    public function isJson(): bool
    {
        $contentType = $this->server['CONTENT_TYPE'] ?? '';
        return str_contains($contentType, '/json')
            || str_contains($contentType, '+json');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function only(array $keys): array
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->input($key);
        }
        return $data;
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    public function has(string $key): bool
    {
        return isset($this->body[$key]) || isset($this->query[$key]);
    }

    public function file(string $key): ?array
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] !== UPLOAD_ERR_NO_FILE
            ? $this->files[$key]
            : null;
    }

    public function hasFile(string $key): bool
    {
        return $this->file($key) !== null;
    }

    public function uri(): string
    {
        $uri = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return $uri ?: '/';
    }

    public function baseUrl(): string
    {
        $protocol = (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') ? 'https' : 'http';
        return $protocol . '://' . ($this->server['HTTP_HOST'] ?? 'localhost');
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    public function page(): string
    {
        return $this->get('page', 'dashboard');
    }

    public function sub(): string
    {
        return $this->get('sub', '');
    }

    public function action(): string
    {
        return $this->input('action', '');
    }

    public function jsonBody(): ?array
    {
        if (!$this->isJson()) {
            return null;
        }
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    public function validate(array $rules): array
    {
        $errors = [];
        $data = $this->all();

        foreach ($rules as $field => $ruleSet) {
            $ruleList = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            $value = $data[$field] ?? null;

            foreach ($ruleList as $rule) {
                if ($rule === 'required' && ($value === null || $value === '')) {
                    $errors[$field][] = "{$field} wajib diisi";
                    break;
                }
                if (str_starts_with($rule, 'min:') && is_string($value)) {
                    $min = (int) substr($rule, 4);
                    if (mb_strlen($value) < $min) {
                        $errors[$field][] = "{$field} minimal {$min} karakter";
                    }
                }
                if (str_starts_with($rule, 'max:') && is_string($value)) {
                    $max = (int) substr($rule, 4);
                    if (mb_strlen($value) > $max) {
                        $errors[$field][] = "{$field} maksimal {$max} karakter";
                    }
                }
                if ($rule === 'numeric' && $value !== null && $value !== '' && !is_numeric($value)) {
                    $errors[$field][] = "{$field} harus angka";
                }
                if ($rule === 'email' && $value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "{$field} harus email valid";
                }
            }
        }

        return $errors;
    }
}
