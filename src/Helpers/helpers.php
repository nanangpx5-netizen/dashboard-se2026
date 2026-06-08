<?php

declare(strict_types=1);

use App\Helpers\Security;

if (!function_exists('e')) {
    function e(mixed $value, string $default = '-'): string
    {
        if ($value === null) {
            return $default;
        }
        return Security::escape((string) $value);
    }
}
