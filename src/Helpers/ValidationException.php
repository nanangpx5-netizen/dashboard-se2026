<?php

declare(strict_types=1);

namespace App\Helpers;

class ValidationException extends \RuntimeException
{
    private array $errors;

    public function __construct(array $errors, string $message = 'Validasi gagal')
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return $this->errors ? reset($this->errors) : '';
    }
}
