<?php
/**
 * Test Connection View — Standalone diagnostic page.
 *
 * Variables:
 *  - $pageTitle     string
 *  - $success       bool
 *  - $testResults   array
 *  - $system        array
 *  - $errors        array
 *  - $configSafe    array
 *
 * Tidak menggunakan layout (sidebar/navbar) karena ini halaman sistem
 * yang harus bisa diakses tanpa login.
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Test Shared Database') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; background: #f8f9fa; }
        .mono { font-family: 'Courier New', Courier, monospace; font-size: 0.85rem; }
        .section-card { border-left: 4px solid #0d6efd; }
        .section-card.pass { border-left-color: #198754; }
        .section-card.fail { border-left-color: #dc3545; }
        .section-card.skip { border-left-color: #6c757d; }
        pre { max-height: 400px; overflow: auto; }
        .live-badge { animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
        .proof-box { background: #f0fdf4; border: 2px solid #198754; border-radius: 8px; }
        .proof-box-fail { background: #fef2f2; border: 2px solid #dc3545; border-radius: 8px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">
            <i class="fas fa-database me-2"></i>Dashboard SE2026 — Shared Database Validator
        </a>
        <span class="navbar-text text-white-50 small">
            <i class="fas fa-circle text-success me-1 live-badge" style="font-size: 0.5rem; vertical-align: middle;"></i>
            LIVE — Real-time Shared Database
        </span>
    </div>
</nav>

<div class="container my-4">

    <?php if ($success): ?>
    <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-3 py-3">
        <i class="fas fa-check-circle fa-2x"></i>
        <div>
            <h6 class="mb-0 fw-bold">
                Dashboard berhasil tersambung <u>realtime</u> dengan database SE2026
                (<code><?= htmlspecialchars($testResults['proof_database']['result']) ?></code>)
            </h6>
            <small>
                Koneksi PDO Singleton ID:
                <strong><?= $testResults['proof_connection']['connection_id'] ?></strong>
                &nbsp;|&nbsp; MySQL:
                <strong><?= htmlspecialchars($testResults['proof_server']['version']) ?></strong>
                &nbsp;|&nbsp; Waktu:
                <strong><?= htmlspecialchars($testResults['proof_server']['server_time']) ?></strong>
            </small>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-danger border-0 shadow-sm">
        <h6 class="fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Validasi Gagal</h6>
        <p class="mb-0 small">Dashboard tidak dapat memvalidasi koneksi shared database. Lihat error di bawah.</p>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2">
            <span class="fw-semibold small"><i class="fas fa-server me-2 text-primary"></i>System Information</span>
        </div>
        <div class="card-body py-2">
            <div class="row g-2 small">
                <div class="col-md-3">
                    <span class="text-muted">PHP:</span>
                    <strong><?= htmlspecialchars($system['php_version']) ?></strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted">Server:</span>
                    <strong><?= htmlspecialchars($system['server_software']) ?></strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted">Waktu Server:</span>
                    <strong><?= htmlspecialchars($system['server_time']) ?></strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted">Timezone:</span>
                    <strong><?= htmlspecialchars($system['server_timezone']) ?></strong>
                </div>
            </div>
            <div class="small mt-1">
                <span class="text-muted">Extensions:</span>
                <code><?= htmlspecialchars($system['loaded_extensions']) ?></code>
            </div>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="card border-0 shadow-sm mb-3 border-danger">
        <div class="card-header bg-danger text-white py-2">
            <span class="fw-semibold small"><i class="fas fa-exclamation-circle me-2"></i>Errors</span>
        </div>
        <div class="card-body p-0">
            <?php foreach ($errors as $e): ?>
            <div class="p-3 border-bottom">
                <span class="badge bg-danger"><?= htmlspecialchars($e['type']) ?></span>
                <p class="mb-1 mt-1"><strong><?= htmlspecialchars($e['message']) ?></strong></p>
                <small class="text-muted mono"><?= htmlspecialchars($e['file']) ?>:<?= $e['line'] ?></small>
                <?php if (!empty($e['trace'])): ?>
                <pre class="small text-muted mt-1 mb-0" style="max-height:100px"><?= htmlspecialchars($e['trace']) ?></pre>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>

    <div class="proof-box p-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <i class="fas fa-check-circle fa-3x text-success"></i>
            <div>
                <h6 class="fw-bold mb-1"><?= $testResults['proof_database']['is_valid'] ? 'VALID' : 'INVALID' ?> — Shared Database Proof</h6>
                <p class="mb-0 small">
                    <code>SELECT DATABASE()</code> →
                    <strong><code><?= htmlspecialchars($testResults['proof_database']['result']) ?></code></strong>
                    (diharapkan: <code><?= htmlspecialchars($testResults['proof_database']['expected']) ?></code>)
                </p>
                <p class="mb-0 small text-muted">
                    Dashboard dan web SE2026 membaca database yang <u>sama persis</u> — tidak ada copy/sync.
                </p>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100 section-card pass">
                <div class="card-header bg-white py-2">
                    <span class="fw-semibold small"><i class="fas fa-plug me-2 text-success"></i>PDO Singleton Validation</span>
                </div>
                <div class="card-body py-3">
                    <table class="table table-sm table-borderless small mb-0">
                        <tr>
                            <td class="text-muted" style="width:160px">Method</td>
                            <td><code>Database::getInstance()</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Query</td>
                            <td><code><?= htmlspecialchars($testResults['proof_connection']['query']) ?></code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Connection ID</td>
                            <td>
                                <strong class="text-success fs-5">
                                    <?= $testResults['proof_connection']['connection_id'] ?>
                                </strong>
                                <span class="badge bg-success ms-2">AKTIF</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Total Queries</td>
                            <td><?= $testResults['realtime_proof']['total_queries'] ?></td>
                        </tr>
                    </table>
                    <div class="small text-muted mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Semua request menggunakan koneksi PDO <strong>singleton</strong> yang sama.
                        Tidak ada multiple connection.
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100 section-card pass">
                <div class="card-header bg-white py-2">
                    <span class="fw-semibold small"><i class="fas fa-server me-2 text-primary"></i>MySQL Server</span>
                </div>
                <div class="card-body py-3">
                    <table class="table table-sm table-borderless small mb-0">
                        <tr>
                            <td class="text-muted" style="width:160px">Version</td>
                            <td><strong><?= htmlspecialchars($testResults['proof_server']['version']) ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Server Time</td>
                            <td><strong><?= htmlspecialchars($testResults['proof_server']['server_time']) ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Timezone</td>
                            <td><?= htmlspecialchars($testResults['proof_server']['timezone']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Active Connections</td>
                            <td><?= $testResults['proof_server']['active_connections'] ?> ke database ini</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3 section-card pass">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <span class="fw-semibold small">
                <i class="fas fa-chart-simple me-2 text-success"></i>Live Table Counts
                <span class="badge bg-success ms-2 live-badge">REAL-TIME</span>
            </span>
            <small class="text-muted">
                Query langsung ke <code><?= htmlspecialchars($testResults['proof_database']['result']) ?></code>
            </small>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm small mb-0">
                <thead class="table-success">
                    <tr>
                        <th>Table</th>
                        <th>Exists</th>
                        <th>Row Count</th>
                        <th>Query</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($testResults['live_counts'] as $label => $info): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($label) ?></code></td>
                        <td>
                            <?php if ($info['exists']): ?>
                                <i class="fas fa-check-circle text-success"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle text-danger"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($info['count'] !== null): ?>
                                <strong><?= number_format($info['count']) ?></strong>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><code>SELECT COUNT(*) FROM <?= htmlspecialchars($info['table_name']) ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-1 small text-muted text-end">
            <i class="fas fa-clock me-1"></i>Data live pada: <?= htmlspecialchars($testResults['proof_server']['server_time']) ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3 section-card pass">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <span class="fw-semibold small"><i class="fas fa-users me-2 text-primary"></i>Sample Data: users</span>
            <span class="badge bg-<?= $testResults['sample_users']['status'] === 'PASS' ? 'success' : ($testResults['sample_users']['status'] === 'EMPTY' ? 'warning' : 'secondary') ?>">
                <?= $testResults['sample_users']['row_count'] ?? 0 ?> rows
            </span>
        </div>
        <?php if (!empty($testResults['sample_users']['rows'])): ?>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm small mb-0">
                    <thead class="table-light">
                        <tr>
                            <?php foreach ($testResults['sample_users']['columns'] as $col): ?>
                                <th><?= htmlspecialchars($col) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($testResults['sample_users']['rows'] as $row): ?>
                        <tr>
                            <?php foreach ($testResults['sample_users']['columns'] as $col): ?>
                                <td><?= htmlspecialchars((string) ($row[$col] ?? '-')) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-1">
            <small class="text-muted">
                <code>SELECT * FROM users LIMIT 10</code>
                &nbsp;—&nbsp; Data langsung dari database existing (read-only)
            </small>
        </div>
        <?php else: ?>
        <div class="card-body py-3">
            <p class="text-muted small mb-0">
                <?= htmlspecialchars($testResults['sample_users']['message'] ?? 'Tabel users kosong atau tidak ditemukan.') ?>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm mb-3 section-card <?= $testResults['sample_wilayah']['status'] === 'PASS' ? 'pass' : 'skip' ?>">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <span class="fw-semibold small"><i class="fas fa-map me-2 text-primary"></i>Sample Data: wilayah_kerja</span>
            <span class="badge bg-<?= $testResults['sample_wilayah']['status'] === 'PASS' ? 'success' : ($testResults['sample_wilayah']['status'] === 'EMPTY' ? 'warning' : 'secondary') ?>">
                <?= $testResults['sample_wilayah']['row_count'] ?? 0 ?> rows
            </span>
        </div>
        <?php if (!empty($testResults['sample_wilayah']['rows'])): ?>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm small mb-0">
                    <thead class="table-light">
                        <tr>
                            <?php foreach ($testResults['sample_wilayah']['columns'] as $col): ?>
                                <th><?= htmlspecialchars($col) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($testResults['sample_wilayah']['rows'] as $row): ?>
                        <tr>
                            <?php foreach ($testResults['sample_wilayah']['columns'] as $col): ?>
                                <td><?= htmlspecialchars((string) ($row[$col] ?? '-')) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-1">
            <small class="text-muted">
                <code>SELECT * FROM wilayah_kerja LIMIT 10</code>
            </small>
        </div>
        <?php else: ?>
        <div class="card-body py-3">
            <p class="text-muted small mb-0">
                <?= htmlspecialchars($testResults['sample_wilayah']['message'] ?? 'Tabel wilayah_kerja kosong. Data akan terisi setelah web SE2026 mengelolanya.') ?>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm mb-3 section-card <?= $testResults['sample_desa']['status'] === 'PASS' ? 'pass' : 'skip' ?>">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <span class="fw-semibold small"><i class="fas fa-location-dot me-2 text-primary"></i>Sample Data: desa</span>
            <span class="badge bg-<?= $testResults['sample_desa']['status'] === 'PASS' ? 'success' : ($testResults['sample_desa']['status'] === 'EMPTY' ? 'warning' : 'secondary') ?>">
                <?= $testResults['sample_desa']['row_count'] ?? 0 ?> rows
            </span>
        </div>
        <?php if (!empty($testResults['sample_desa']['rows'])): ?>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm small mb-0">
                    <thead class="table-light">
                        <tr>
                            <?php foreach ($testResults['sample_desa']['columns'] as $col): ?>
                                <th><?= htmlspecialchars($col) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($testResults['sample_desa']['rows'] as $row): ?>
                        <tr>
                            <?php foreach ($testResults['sample_desa']['columns'] as $col): ?>
                                <td><?= htmlspecialchars((string) ($row[$col] ?? '-')) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-1">
            <small class="text-muted">
                <?= htmlspecialchars($testResults['sample_desa']['note'] ?? '') ?>
                <code>SELECT * FROM desa/wilayah_kerja LIMIT 10</code>
            </small>
        </div>
        <?php else: ?>
        <div class="card-body py-3">
            <p class="text-muted small mb-0">
                <?= htmlspecialchars($testResults['sample_desa']['message'] ?? 'Data desa tidak tersedia.') ?>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <div class="proof-box p-3 mb-4">
        <div class="d-flex align-items-start gap-3">
            <div>
                <i class="fas fa-bolt fa-2x text-success"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-1">✓ REAL-TIME SHARED DATABASE VALID</h6>
                <p class="mb-1 small">
                    Dashboard <strong>terbukti</strong> membaca database <code><?= htmlspecialchars($testResults['realtime_proof']['database']) ?></code>
                    secara langsung. Semua query adalah <strong>live query</strong> tanpa cache.
                </p>

                <h6 class="fw-semibold small mt-3 mb-1">Cara Membuktikan Real-time:</h6>
                <ol class="small mb-0">
                    <?php foreach ($testResults['realtime_proof']['how_to_prove'] as $step): ?>
                        <li><?= htmlspecialchars($step) ?></li>
                    <?php endforeach; ?>
                    <li class="fw-bold text-success mt-1">
                        Jika berubah langsung → VALID = shared realtime database
                    </li>
                </ol>

                <div class="row g-2 mt-3">
                    <div class="col-md-6">
                        <div class="bg-light p-2 rounded">
                            <small class="text-muted d-block">Contoh Sebelum:</small>
                            <code>users count = <?= $testResults['realtime_proof']['table_counts']['users'] ?></code>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light p-2 rounded">
                            <small class="text-muted d-block">Tambah user di SE2026 → Refresh →</small>
                            <code class="text-success fw-bold">users count = <span class="live-badge">LIVE</span></code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <span class="fw-semibold small"><i class="fas fa-table me-2 text-muted"></i>All Tables in Database</span>
            <span class="badge bg-secondary"><?= count($testResults['all_tables']) ?> tables</span>
        </div>
        <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
            <table class="table table-sm small mb-0">
                <thead class="table-light" style="position: sticky; top: 0;">
                    <tr><th>#</th><th>Table Name</th><th>Row Count</th></tr>
                </thead>
                <tbody>
                    <?php $i = 1; ?>
                    <?php foreach ($testResults['all_tables'] as $tbl): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><code><?= htmlspecialchars($tbl['name']) ?></code></td>
                        <td><?= number_format($tbl['count']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2">
            <span class="fw-semibold small"><i class="fas fa-cog me-2 text-muted"></i>Database Configuration</span>
        </div>
        <div class="card-body py-2">
            <table class="table table-sm table-borderless small mb-0" style="max-width: 500px;">
                <?php foreach ($configSafe as $key => $val): ?>
                <tr>
                    <td class="text-muted" style="width: 150px;"><?= htmlspecialchars($key) ?></td>
                    <td><code><?= htmlspecialchars((string) $val) ?></code></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-2">
            <span class="fw-semibold small"><i class="fas fa-wrench me-2 text-muted"></i>Troubleshooting</span>
        </div>
        <div class="card-body py-2">
            <table class="table table-sm small mb-0">
                <thead class="table-light">
                    <tr><th>Error</th><th>Penyebab</th><th>Solusi</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="badge bg-danger">Connection failed</span></td>
                        <td>MySQL mati atau credential salah</td>
                        <td><code>net start mysql</code> atau start Laragon. Cek .env</td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-danger">.env not found</span></td>
                        <td>File .env belum dibuat</td>
                        <td><code>copy .env.example .env</code> lalu isi konfigurasi</td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-danger">Access denied</span></td>
                        <td>Username/password MySQL salah</td>
                        <td>Cek <code>DB_USERNAME</code> dan <code>DB_PASSWORD</code> di .env</td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-warning">Table not found</span></td>
                        <td>Tabel dashboard belum tersedia</td>
                        <td>Jalankan SQL patch di <code>database/patch_*.sql</code></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-warning">PDO Exception</span></td>
                        <td>Error koneksi database</td>
                        <td>Cek <code>storage/logs/database.log</code> untuk detail</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
