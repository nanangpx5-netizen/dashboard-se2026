<?php

declare(strict_types=1);

use App\Helpers\Security;

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return Security::escape($value ?? '');
    }
}
