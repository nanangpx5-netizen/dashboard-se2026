<?php

/**
 * Konfigurasi Dashboard SE2026
 * Loaded oleh App::boot() via bootstrap
 */

defined('VIEW_PATH') || define('VIEW_PATH', __DIR__ . '/../views');
defined('STORAGE_PATH') || define('STORAGE_PATH', __DIR__ . '/../storage');
defined('UPLOAD_PATH') || define('UPLOAD_PATH', STORAGE_PATH . '/uploads');
defined('LOG_PATH') || define('LOG_PATH', STORAGE_PATH . '/logs');
defined('CACHE_PATH') || define('CACHE_PATH', STORAGE_PATH . '/cache');
defined('UPLOAD_SIPW_PATH') || define('UPLOAD_SIPW_PATH', UPLOAD_PATH . '/sipw');

defined('APP_NAME') || define('APP_NAME', 'Dashboard SE2026 Jember');
defined('APP_ENV') || define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
defined('APP_DEBUG') || define('APP_DEBUG', (bool) ($_ENV['APP_DEBUG'] ?? false));

defined('MAX_UPLOAD_SIZE') || define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10 MB
defined('ALLOWED_EXTENSIONS') || define('ALLOWED_EXTENSIONS', ['xlsx', 'xls', 'csv']);

defined('PAGINATION_PER_PAGE') || define('PAGINATION_PER_PAGE', 20);

defined('SE2026_START_DATE') || define('SE2026_START_DATE', '2026-05-01');
defined('SE2026_END_DATE') || define('SE2026_END_DATE', '2026-07-31');
