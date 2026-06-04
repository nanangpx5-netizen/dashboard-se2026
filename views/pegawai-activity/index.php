<?php
/** @var array $summary */
/** @var array $per_user */
/** @var array $by_action */
/** @var array $by_day */
/** @var int   $days */

$byActionJson = json_encode($by_action, JSON_HEX_TAG | JSON_HEX_AMP);
$byDayJson    = json_encode($by_day, JSON_HEX_TAG | JSON_HEX_AMP);
$perUserJson  = json_encode($per_user, JSON_HEX_TAG | JSON_HEX_AMP);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="fas fa-user-clock me-2 text-se2026"></i>Rekap Aktivitas Pegawai</h5>
    <small class="text-muted">Periode: <?= $days ?> hari terakhir (<?= date('d M Y', strtotime("-{$days} days")) ?> s.d. <?= date('d M Y') ?>)</small>
</div>

<div class="row g-2 g-md-3 mb-4">
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card card-jember h-100 border-0 shadow-sm">
            <div class="card-body py-2">
                <small class="text-muted d-block">Total Pegawai</small>
                <span class="fw-bold fs-4"><?= number_format($summary['total_pegawai']) ?></span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card border-0 shadow-sm h-100" style="border-left:3px solid #198754 !important">
            <div class="card-body py-2">
                <small class="text-muted d-block">Active</small>
                <span class="fw-bold fs-4 text-success"><?= number_format($summary['active_pegawai']) ?></span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card border-0 shadow-sm h-100" style="border-left:3px solid #ffc107 !important">
            <div class="card-body py-2">
                <small class="text-muted d-block">Idle &gt;14 hari</small>
                <span class="fw-bold fs-4 text-warning"><?= number_format($summary['idle_pegawai']) ?></span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card border-0 shadow-sm h-100" style="border-left:3px solid #dc3545 !important">
            <div class="card-body py-2">
                <small class="text-muted d-block">Never Login</small>
                <span class="fw-bold fs-4 text-danger"><?= number_format($summary['never_login']) ?></span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card border-0 shadow-sm h-100" style="border-left:3px solid #F47B20 !important">
            <div class="card-body py-2">
                <small class="text-muted d-block">Aksi <?= $days ?>d</small>
                <span class="fw-bold fs-4 text-se2026"><?= number_format($summary['total_actions_30d']) ?></span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card border-0 shadow-sm h-100" style="border-left:3px solid #0d6efd !important">
            <div class="card-body py-2">
                <small class="text-muted d-block">Login <?= $days ?>d</small>
                <span class="fw-bold fs-4 text-primary"><?= number_format($summary['login_count_30d']) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2">
                <small class="fw-semibold"><i class="fas fa-chart-pie me-1 text-se2026"></i>Aksi per Tipe (<?= $days ?>d)</small>
            </div>
            <div class="card-body">
                <canvas id="chartActionType" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2">
                <small class="fw-semibold"><i class="fas fa-chart-line me-1 text-se2026"></i>Aktivitas Harian</small>
            </div>
            <div class="card-body">
                <canvas id="chartDaily" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-2">
        <small class="fw-semibold"><i class="fas fa-users me-1 text-se2026"></i>Per-Pegawai Breakdown</small>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover small mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Nama</th>
                    <th>Status</th>
                    <th>Kecamatan Bertugas</th>
                    <th>Last Login</th>
                    <th class="text-end">Aksi <?= $days ?>d</th>
                    <th class="text-end">Login</th>
                    <th class="text-end">IP Unik</th>
                    <th class="text-center">Produktivitas</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($per_user)): ?>
                <tr><td colspan="10" class="text-center text-muted py-3">Belum ada data pegawai.</td></tr>
                <?php else: ?>
                <?php $no = 1; foreach ($per_user as $u): ?>
                <?php
                    $isIdle = $u['last_login_at'] === null || strtotime($u['last_login_at']) < strtotime('-14 days');
                    $isNeverLogin = $u['last_login_at'] === null;
                    $prodPct = $u['logins_30d'] > 0 ? min(100, round(($u['logins_30d'] / 20) * 100)) : 0;
                    $badgeClass = $isNeverLogin ? 'bg-danger' : ($isIdle ? 'bg-warning text-dark' : 'bg-success');
                    $badgeText = $isNeverLogin ? 'Never' : ($isIdle ? 'Idle' : 'Aktif');
                ?>
                <tr>
                    <td class="text-center text-muted"><?= $no++ ?></td>
                    <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                    <td><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                    <td><span class="badge <?= $u['status_akun'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= htmlspecialchars($u['status_akun']) ?></span></td>
                    <td class="small"><?= htmlspecialchars($u['kecamatan'] ?? '-') ?></td>
                    <td class="small text-muted"><?= $u['last_login_at'] ? date('d M Y H:i', strtotime($u['last_login_at'])) : '<span class="text-danger">never</span>' ?></td>
                    <td class="text-end"><?= number_format($u['actions_30d']) ?></td>
                    <td class="text-end"><?= number_format($u['logins_30d']) ?></td>
                    <td class="text-end"><?= number_format($u['distinct_ip']) ?></td>
                    <td class="text-center">
                        <div class="d-flex align-items-center gap-1">
                            <div class="progress flex-grow-1" style="height:6px;min-width:50px">
                                <div class="progress-bar <?= $isNeverLogin ? 'bg-danger' : 'bg-success' ?>" style="width:<?= $prodPct ?>%"></div>
                            </div>
                            <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="alert alert-info mt-3 small mb-0">
    <i class="fas fa-info-circle me-1"></i>
    <strong>Threshold:</strong> Idle jika last_login &gt; 14 hari yang lalu. Never = belum pernah login sejak akun dibuat.
    <br><strong>Rekomendasi R3.6:</strong> Minimal 2 pegawai aktif per siklus penugasan untuk backup.
</div>

<script id="pegawaiActivityData" type="application/json">
{
    "byAction": <?= $byActionJson ?>,
    "byDay": <?= $byDayJson ?>,
    "perUser": <?= $perUserJson ?>
}
</script>
