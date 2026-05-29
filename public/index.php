<?php

/**
 * Dashboard SE2026 — Shared Database Connection Test
 *
 * Entry point untuk verifikasi koneksi real-time
 * ke database bps_jember_se2026.
 *
 * Akses: /test-connection
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Controllers\TestConnectionController;

try {
    $controller = new TestConnectionController();
    $controller->index();
} catch (\Throwable $e) {
    http_response_code(500);
    $message = htmlspecialchars($e->getMessage());
    $file    = htmlspecialchars($e->getFile());
    $line    = $e->getLine();
    $trace   = htmlspecialchars($e->getTraceAsString());

    echo <<<HTML
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Connection Error</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container py-5">
            <div class="alert alert-danger shadow-sm">
                <h5 class="alert-heading fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Shared Database Connection Gagal</h5>
                <hr>
                <p class="mb-1"><strong>Error:</strong> {$message}</p>
                <p class="mb-0 small text-muted">{$file}:{$line}</p>
                <hr class="my-2">
                <pre class="small mb-0" style="max-height:300px;overflow:auto;">{$trace}</pre>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-semibold">Troubleshooting:</h6>
                    <ol class="small mb-0">
                        <li>Pastikan file <code>.env</code> ada di root project</li>
                        <li>Pastikan <code>.env</code> berisi konfigurasi database yang benar</li>
                        <li>Pastikan MySQL server berjalan</li>
                        <li>Pastikan database <code>bps_jember_se2026</code> ada</li>
                        <li>Cek kredensial database (username/password)</li>
                        <li>Jalankan: <code>mysql -u root -e "SELECT 1" bps_jember_se2026</code></li>
                    </ol>
                </div>
            </div>
        </div>
    </body>
    </html>
    HTML;
    exit;
}
