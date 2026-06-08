<?php
/** @var array $summary */
/** @var array $anomali */
/** @var array $beban */
/** @var array $distribusi */
/** @var array $coverage */
/** @var array $rekomendasi */
/** @var array $user_pool */
/** @var array $quality */


$bebanJson  = json_encode($beban, JSON_HEX_TAG | JSON_HEX_AMP);
$distJson   = json_encode($distribusi, JSON_HEX_TAG | JSON_HEX_AMP);
$covJson    = json_encode($coverage, JSON_HEX_TAG | JSON_HEX_AMP);
$anomJson   = json_encode($anomali, JSON_HEX_TAG | JSON_HEX_AMP);
$userJson   = json_encode($user_pool, JSON_HEX_TAG | JSON_HEX_AMP);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2 text-se2026"></i>Insight &amp; Analisa</h5>
    <div class="small text-muted">
        <i class="fas fa-clock me-1"></i>Update terakhir: <?= date('d F Y H:i') ?> WIB
        <span class="badge bg-se2026 ms-2">SE2026</span>
    </div>
</div>

<?php if (!empty($rekomendasi)): ?>
<div class="card border-0 shadow-sm mb-4 border-start border-4 border-se2026">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="fas fa-lightbulb me-2 text-warning"></i>Rekomendasi Otomatis</h6>
        <div class="row g-2">
            <?php foreach ($rekomendasi as $r): ?>
                <div class="col-md-6">
                    <div class="alert alert-<?= htmlspecialchars($r['level']) ?> py-2 px-3 mb-2 d-flex align-items-start gap-2">
                        <i class="fas <?= htmlspecialchars($r['icon']) ?> mt-1"></i>
                        <div class="flex-grow-1">
                            <strong class="d-block"><?= e($r['title']) ?></strong>
                            <small><?= e($r['desc']) ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($delta_detail)): ?>
<div class="card border-0 shadow-sm mb-4 border-start border-4 border-se2026">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="fas fa-layer-group me-2 text-se2026"></i>Analisis Selisih <?= number_format($summary['delta_sls_vs_prelist'] ?? 0) ?> SLS — Multi Sub-SLS</h6>
            <span class="badge bg-se2026"><?= number_format($delta_total_sls) ?> sub-SLS</span>
        </div>
        <div class="alert alert-info py-2 px-3 small mb-3">
            <i class="fas fa-info-circle me-1"></i>
            Selisih <strong><?= number_format($summary['delta_sls_vs_prelist']) ?></strong> SLS antara SIPW (<?= number_format($summary['total_sls']) ?>) dan Prelist (<?= number_format($summary['prelist_sls']) ?>)
            disebabkan oleh <strong>multi sub-SLS</strong> — beberapa SLS di lapangan dipecah menjadi beberapa sub-SLS (penomoran 16 digit, 2 digit akhir = sub-SLS).
            Prelist hanya mencatat 1 baris per SLS (14 digit). Bukan duplikasi error — data sah dan perlu diakomodasi dalam proses agregasi.
        </div>
        <div class="row g-3 mb-3">
            <div class="col-lg-8">
                <canvas id="chartDeltaSls" height="180"></canvas>
            </div>
            <div class="col-lg-4">
                <div class="table-responsive" style="max-height:220px;overflow-y:auto">
                    <table class="table table-sm table-hover small mb-0">
                        <thead class="table-light sticky-top">
                            <tr><th>Kecamatan</th><th class="text-end">SLS</th><th class="text-end">Sub-SLS</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($delta_detail as $d): ?>
                            <tr class="cursor-pointer delta-kec-row" data-kdkec="<?= htmlspecialchars($d['kdkec']) ?>">
                                <td><?= htmlspecialchars($d['nmkec']) ?></td>
                                <td class="text-end"><?= number_format($d['sls_unik']) ?></td>
                                <td class="text-end text-se2026 fw-bold"><?= number_format($d['sls_extra']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="fw-semibold"><i class="fas fa-list me-1"></i>Daftar SLS dengan Multi Sub-SLS</small>
            <div class="d-flex gap-2 align-items-center">
                <select id="filterDeltaKec" class="form-select form-select-sm" style="width:auto">
                    <option value="">Semua Kecamatan</option>
                    <?php foreach ($delta_kec as $k): ?>
                    <option value="<?= htmlspecialchars($k['kdkec']) ?>"><?= htmlspecialchars($k['nmkec']) ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="badge bg-secondary" id="deltaSlsCount"><?= count($delta_detail_sls) ?> SLS</span>
            </div>
        </div>
        <div class="table-responsive" style="max-height:400px;overflow-y:auto">
            <table class="table table-sm table-hover small mb-0" id="tblDeltaSls">
                <thead class="table-light sticky-top">
                    <tr>
                        <th style="width:40px">#</th>
                        <th>ID SLS</th>
                        <th>Kec</th>
                        <th>Desa</th>
                        <th>Nama SLS</th>
                        <th class="text-end">Baris</th>
                        <th class="text-end">KK</th>
                        <th class="text-end">Muatan</th>
                        <th class="text-end">BTT</th>
                        <th class="text-end">Usaha</th>
                        <th class="text-center">Kualitas</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($delta_detail_sls as $s): ?>
                    <tr class="delta-sls-row" data-kdkec="<?= htmlspecialchars($s['kdkec']) ?>" data-idsls="<?= htmlspecialchars($s['idsls']) ?>">
                        <td class="text-center text-muted"><?= $no++ ?></td>
                        <td><code><?= htmlspecialchars($s['idsls']) ?></code></td>
                        <td><?= htmlspecialchars($s['nmkec']) ?></td>
                        <td><?= htmlspecialchars($s['nmdesa']) ?></td>
                        <td><?= htmlspecialchars($s['nmsls']) ?></td>
                        <td class="text-end fw-bold text-se2026"><?= max(0, $s['total_baris'] - 1) ?>x</td>
                        <td class="text-end"><?= number_format($s['total_kk']) ?></td>
                        <td class="text-end"><?= number_format($s['total_muatan']) ?></td>
                        <td class="text-end"><?= number_format($s['total_btt']) ?></td>
                        <td class="text-end"><?= number_format($s['total_usaha']) ?></td>
                        <td class="text-center">
                            <?php if ($s['sub_kk_zero'] == $s['total_baris']): ?>
                                <span class="badge bg-danger" title="Semua sub-SLS KK=0">Error</span>
                            <?php elseif ($s['sub_identical'] > 0): ?>
                                <span class="badge bg-warning text-dark" title="<?= $s['sub_identical'] ?> sub-SLS identik"><?= $s['sub_identical'] ?>/<?= $s['total_baris'] ?></span>
                            <?php else: ?>
                                <span class="badge bg-success" title="Data sub-SLS bervariasi">OK</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-se2026 py-0 px-2 toggle-sub-detail" data-idsls="<?= htmlspecialchars($s['idsls']) ?>">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="d-none sub-detail-row" data-idsls="<?= htmlspecialchars($s['idsls']) ?>">
                        <td colspan="12" class="bg-light p-3">
                            <div class="small">
                                <div class="d-flex gap-3 mb-2">
                                    <span class="text-muted"><strong>Total sub-SLS:</strong> <?= $s['total_baris'] ?></span>
                                    <span class="text-danger"><strong>KK=0:</strong> <?= $s['sub_kk_zero'] ?></span>
                                    <?php if ($s['sub_identical'] > 0): ?>
                                    <span class="text-warning"><strong>Identik (muatan=bku=usaha):</strong> <?= $s['sub_identical'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <table class="table table-sm table-bordered mb-0 small" style="width:auto">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Sub-SLS ID</th>
                                            <th>ID Row</th>
                                            <th class="text-end">KK</th>
                                            <th class="text-end">Muatan</th>
                                            <th class="text-end">BTT</th>
                                            <th class="text-end">BKU</th>
                                            <th class="text-end">Usaha</th>
                                            <th class="text-center">Indikasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td colspan="9" class="text-center text-muted py-2"><span class="spinner-border spinner-border-sm me-2"></span>Memuat...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($delta_detail_sls)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-3">Tidak ada SLS dengan multi sub-SLS</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-2 mb-4">
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm h-100 border-top border-3 border-se2026">
            <div class="card-body py-3">
                <small class="text-muted d-block">Total SLS</small>
                <h3 class="fw-bold mb-0 mt-1"><?= number_format($summary['total_sls'] ?? 0) ?></h3>
                <small class="text-muted"><?= number_format($summary['total_kec'] ?? 0) ?> kecamatan</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm h-100 border-top border-3 border-primary">
            <div class="card-body py-3">
                <small class="text-muted d-block">KK</small>
                <h3 class="fw-bold mb-0 mt-1"><?= number_format($summary['total_kk'] ?? 0) ?></h3>
                <small class="text-muted">avg <?= number_format($summary['avg_kk'] ?? 0, 1) ?>/SLS</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm h-100 border-top border-3 border-warning">
            <div class="card-body py-3">
                <small class="text-muted d-block">Muatan</small>
                <h3 class="fw-bold mb-0 mt-1"><?= number_format($summary['total_muatan'] ?? 0) ?></h3>
                <small class="text-muted">avg <?= number_format($summary['avg_muatan'] ?? 0, 1) ?>/SLS</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm h-100 border-top border-3 border-success">
            <div class="card-body py-3">
                <small class="text-muted d-block">BTT</small>
                <h3 class="fw-bold mb-0 mt-1"><?= number_format($summary['total_btt'] ?? 0) ?></h3>
                <small class="text-muted">avg <?= number_format($summary['avg_btt'] ?? 0, 1) ?>/SLS</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm h-100 border-top border-3 border-info">
            <div class="card-body py-3">
                <small class="text-muted d-block">Assignment</small>
                <h3 class="fw-bold mb-0 mt-1"><?= number_format($summary['total_assignment'] ?? 0) ?></h3>
                <small class="text-muted"><?= number_format($summary['assignment_pct'] ?? 0, 1) ?>% coverage</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm h-100 border-top border-3 border-danger">
            <div class="card-body py-3">
                <small class="text-muted d-block">Anomali</small>
                <h3 class="fw-bold mb-0 mt-1"><?= number_format($total_anomali) ?></h3>
                <small class="text-muted"><?= $anomali_pct ?>% muatan=0</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold">
                    <i class="fas fa-exclamation-triangle me-1 text-danger"></i>Anomali per Kecamatan
                </small>
                <div>
                    <a href="?page=dashboard&sub=insight&action=export&type=anomali" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Export CSV">
                        <i class="fas fa-download"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-2">
                <canvas id="chartAnomaliKec" height="280"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold">
                    <i class="fas fa-chart-area me-1 text-se2026"></i>Distribusi Muatan
                </small>
                <div>
                    <a href="?page=dashboard&sub=insight&action=export&type=distribusi" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Export CSV">
                        <i class="fas fa-download"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-2">
                <canvas id="chartDistribusi" height="280"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold">
                    <i class="fas fa-weight-hanging me-1 text-warning"></i>Beban Kerja per Kecamatan
                </small>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-success">RINGAN < 50</span>
                    <span class="badge bg-warning text-dark">SEDANG 50-100</span>
                    <span class="badge bg-danger">BERAT &gt; 100</span>
                    <a href="?page=dashboard&sub=insight&action=export&type=beban" class="btn btn-sm btn-outline-secondary py-0 px-2 ms-2" title="Export CSV">
                        <i class="fas fa-download"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-2">
                <canvas id="chartBebanKerja" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold">
                    <i class="fas fa-table me-1 text-se2026"></i>Tabel Beban Kerja (klik untuk filter)
                </small>
                <input type="text" id="searchBeban" class="form-control form-control-sm w-auto" placeholder="Cari kecamatan...">
            </div>
            <div class="card-body p-2">
                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-sm table-hover small mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Kecamatan</th>
                                <th class="text-end">SLS</th>
                                <th class="text-end">Avg Muatan</th>
                                <th class="text-end">Std Dev</th>
                                <th class="text-end">Avg KK</th>
                                <th class="text-center">Kategori</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($beban as $r): ?>
                                <tr data-kec="<?= htmlspecialchars($r['nmkec']) ?>">
                                    <td><strong><?= htmlspecialchars($r['nmkec']) ?></strong></td>
                                    <td class="text-end"><?= number_format($r['sls']) ?></td>
                                    <td class="text-end"><?= number_format($r['avg_mu'], 1) ?></td>
                                    <td class="text-end"><?= number_format($r['std_mu'], 1) ?></td>
                                    <td class="text-end"><?= number_format($r['avg_kk'], 1) ?></td>
                                    <td class="text-center">
                                        <?php
                                        $cat = $r['kategori_beban'];
                                        $cls = $cat === 'RINGAN' ? 'success' : ($cat === 'SEDANG' ? 'warning text-dark' : 'danger');
                                        ?>
                                        <span class="badge bg-<?= $cls ?>"><?= $cat ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold">
                    <i class="fas fa-users me-1 text-info"></i>User Pool untuk Assignment
                </small>
            </div>
            <div class="card-body p-3">
                <?php
                $pool = [];
                foreach ($user_pool as $u) {
                    $pool[$u['role']][$u['status_akun']] = $u['jumlah'];
                }
                $roleLabels = ['pcl' => 'PCL', 'pml' => 'PML', 'task_force' => 'Task Force'];
                ?>
                <div style="max-height:200px;position:relative">
                    <canvas id="chartUserPool" height="200"></canvas>
                </div>
                <hr>
                <table class="table table-sm table-borderless small mb-0">
                    <thead><tr><th>Role</th><th class="text-end">Aktif</th><th class="text-end">Non-aktif</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($roleLabels as $r => $lbl): ?>
                            <tr>
                                <td><strong><?= $lbl ?></strong></td>
                                <td class="text-end text-success"><?= number_format($pool[$r]['active'] ?? 0) ?></td>
                                <td class="text-end text-muted"><?= number_format($pool[$r]['inactive'] ?? 0) ?></td>
                                <td class="text-end fw-bold"><?= number_format(($pool[$r]['active'] ?? 0) + ($pool[$r]['inactive'] ?? 0)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold">
                    <i class="fas fa-exchange-alt me-1 text-se2026"></i>Coverage Gap: Prelist vs Actual
                </small>
                <div>
                    <span class="badge bg-info">Selisih <?= number_format($summary['delta_sls_vs_prelist'] ?? 0) ?> SLS</span>
                    <a href="?page=dashboard&sub=insight&action=export&type=coverage" class="btn btn-sm btn-outline-secondary py-0 px-2 ms-2" title="Export CSV">
                        <i class="fas fa-download"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-2">
                <canvas id="chartCoverage" height="120"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold">
                    <i class="fas fa-bug me-1 text-danger"></i>Top SLS Anomali (muatan=0)
                </small>
                <select id="anomaliType" class="form-select form-select-sm w-auto">
                    <option value="muatan_zero">Muatan = 0</option>
                    <option value="kk_zero">KK = 0</option>
                    <option value="muatan_extreme">Muatan &gt; 200</option>
                </select>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-sm table-hover small mb-0" id="tblAnomali">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Kec</th>
                                <th>Desa/SLS</th>
                                <th class="text-end">KK</th>
                                <th class="text-end">Muatan</th>
                                <th class="text-end">BTT</th>
                                <th class="text-end">Usaha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="6" class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Memuat...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold">
                    <i class="fas fa-database me-1 text-secondary"></i>Data Quality
                </small>
            </div>
            <div class="card-body p-3 small">
                <table class="table table-sm table-borderless mb-3">
                    <tr>
                        <td>Last import prelist</td>
                                        <td class="text-end">
                                            <?= $quality['last_import']['last_at']
                                                ? date('d M Y H:i', strtotime($quality['last_import']['last_at']))
                                                : '—' ?>
                                        </td>
                    </tr>
                    <tr>
                        <td>Users aktif</td>
                        <td class="text-end"><?= number_format($quality['user_count_active']) ?></td>
                    </tr>
                    <tr>
                        <td>Activity log hari ini</td>
                        <td class="text-end"><?= number_format($quality['activity_today']) ?></td>
                    </tr>
                    <tr>
                        <td>Total assignment</td>
                        <td class="text-end"><?= number_format($quality['assignment_count']) ?></td>
                    </tr>
                    <tr>
                        <td>Collation konsisten</td>
                        <td class="text-end">
                            <?php if ($quality['collation_consistent']): ?>
                                <span class="badge bg-success">Ya</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Campuran</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Empty tables</td>
                        <td class="text-end"><?= count($quality['empty_tables']) ?></td>
                    </tr>
                </table>

                <details class="mb-2">
                    <summary class="text-muted small">Collation per tabel</summary>
                    <ul class="list-unstyled ms-3 mt-2 small">
                        <?php foreach ($quality['table_collations'] as $tbl => $coll): ?>
                            <li><code><?= $tbl ?></code> = <span class="text-muted"><?= $coll ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </details>

                <?php if (!empty($quality['empty_tables'])): ?>
                    <details>
                        <summary class="text-muted small">Tabel kosong (<?= count($quality['empty_tables']) ?>)</summary>
                        <ul class="list-unstyled ms-3 mt-2 small">
                            <?php foreach ($quality['empty_tables'] as $t): ?>
                                <li><code><?= $t ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
window.INSIGHT_DATA = {
    beban: <?= $bebanJson ?>,
    distribusi: <?= $distJson ?>,
    coverage: <?= $covJson ?>,
    anomali: <?= $anomJson ?>,
    userPool: <?= $userJson ?>,
    deltaDetail: <?= json_encode($delta_detail, JSON_HEX_TAG | JSON_HEX_AMP) ?>,
    deltaDetailSls: <?= json_encode($delta_detail_sls, JSON_HEX_TAG | JSON_HEX_AMP) ?>
};
</script>
