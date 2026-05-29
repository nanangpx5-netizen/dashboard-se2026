<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

// ─── Load .env ──────────────────────────────────────────────
$envFile = APP_ROOT . '/.env';
if (!is_file($envFile)) {
    fwrite(STDERR, "ERROR: .env file not found at {$envFile}\n");
    exit(1);
}

// Manual .env parse (avoid Env class dependency in bootstrap)
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) continue;
    if (!str_contains($line, '=')) continue;
    [$key, $value] = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value);
}

// ─── Composer autoload ──────────────────────────────────────
$autoload = APP_ROOT . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "ERROR: Composer autoload not found. Run: composer install\n");
    exit(1);
}
require $autoload;

// ─── Forward DB_* vars to old app ───────────────────────────
// The existing src/ code reads DB_NAME, DB_USER, DB_PASS
foreach (['DB_NAME' => 'DB_DATABASE', 'DB_USER' => 'DB_USERNAME', 'DB_PASS' => 'DB_PASSWORD'] as $old => $new) {
    if (!isset($_ENV[$old]) && isset($_ENV[$new])) {
        $_ENV[$old] = $_ENV[$new];
    }
}
