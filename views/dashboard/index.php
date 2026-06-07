<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="fas fa-gauge-high me-2"></i>Dashboard SE2026</h5>
    <small class="text-muted"><?= date('d F Y H:i') ?> WIB</small>
</div>

<div class="section-label-jember mb-3">
    <i class="fas fa-city"></i> Data Kabupaten Jember
    <span class="badge badge-jember ms-1">SIPW Import</span>
</div>

<div class="row g-2 g-md-3 mb-4">
    <div class="col-6 col-md-4 col-xl-4">
        <div class="card card-jember h-100 border-0 shadow-sm">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="flex-shrink-0 text-warning fs-3"><i class="fas fa-layer-group"></i></div>
                <div>
                    <small class="text-muted d-block">Subsektor ST2023</small>
                    <span class="fw-bold fs-4"><?= number_format($stats['total_subsektor'] ?? 0) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-4">
        <div class="card card-jember h-100 border-0 shadow-sm">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="flex-shrink-0 text-success fs-3"><i class="fas fa-house-user"></i></div>
                <div>
                    <small class="text-muted d-block">Jml KK (Prelist)</small>
                    <span class="fw-bold fs-4"><?= number_format($stats['total_jml_kk'] ?? 0) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-4">
        <div class="card card-jember h-100 border-0 shadow-sm">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="flex-shrink-0 text-danger fs-3"><i class="fas fa-briefcase"></i></div>
                <div>
                    <small class="text-muted d-block">Usaha Wilkerstat</small>
                    <span class="fw-bold fs-4"><?= number_format($stats['total_usaha_wilker'] ?? 0) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-2 g-md-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-jember h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="flex-shrink-0 text-jember fs-3 glass-icon"><i class="fas fa-map"></i></div>
                <div class="min-w-0">
                    <small class="text-muted d-block text-truncate">Kecamatan</small>
                    <span class="fw-bold fs-4"><?= number_format($stats['total_kecamatan']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-jember h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="flex-shrink-0 text-jember fs-3 glass-icon"><i class="fas fa-location-dot"></i></div>
                <div class="min-w-0">
                    <small class="text-muted d-block text-truncate">Desa</small>
                    <span class="fw-bold fs-4"><?= number_format($stats['total_desa']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-jember h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="flex-shrink-0 text-jember fs-3 glass-icon"><i class="fas fa-database"></i></div>
                <div class="min-w-0">
                    <small class="text-muted d-block text-truncate">Total SLS</small>
                    <span class="fw-bold fs-4"><?= number_format($stats['total_sls']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-jember h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="flex-shrink-0 text-jember fs-3 glass-icon"><i class="fas fa-users"></i></div>
                <div class="min-w-0">
                    <small class="text-muted d-block text-truncate">Total KK</small>
                    <span class="fw-bold fs-4"><?= number_format($stats['total_kk']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-jember h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="flex-shrink-0 text-jember fs-3 glass-icon"><i class="fas fa-store"></i></div>
                <div class="min-w-0">
                    <small class="text-muted d-block text-truncate">Total Usaha</small>
                    <span class="fw-bold fs-4"><?= number_format($stats['total_usaha']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-jember h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="flex-shrink-0 text-jember fs-3 glass-icon"><i class="fas fa-weight"></i></div>
                <div class="min-w-0">
                    <small class="text-muted d-block text-truncate">Total Muatan</small>
                    <span class="fw-bold fs-4"><?= number_format($stats['total_muatan']) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-2 g-md-3 mb-4">
    <div class="col-6 col-md-4 col-xl-4">
        <div class="card card-jember h-100 border-0 shadow-sm">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="flex-shrink-0 text-jember fs-3"><i class="fas fa-user-check"></i></div>
                <div>
                    <small class="text-muted d-block">Pencacah (PCL) <span class="badge badge-jember" style="font-size:8px">Jember</span></small>
                    <span class="fw-bold fs-4"><?= number_format($stats['total_pencacah']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-4">
        <div class="card card-jember h-100 border-0 shadow-sm">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="flex-shrink-0 text-jember fs-3"><i class="fas fa-user-tie"></i></div>
                <div>
                    <small class="text-muted d-block">Pengawas (PML) <span class="badge badge-jember" style="font-size:8px">Jember</span></small>
                    <span class="fw-bold fs-4"><?= number_format($stats['total_pengawas']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-4">
        <div class="card card-jember h-100 border-0 shadow-sm">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="flex-shrink-0 text-jember fs-3"><i class="fas fa-people-group"></i></div>
                <div>
                    <small class="text-muted d-block">Task Force <span class="badge badge-jember" style="font-size:8px">Jember</span></small>
                    <span class="fw-bold fs-4"><?= number_format($stats['total_task_force']) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($prelist_imported && !empty($prelist_kpi)): ?>
<div class="section-label-jatim mb-3">
    <i class="fas fa-globe"></i> Data Provinsi Jawa Timur
    <span class="badge badge-jatim ms-1">Prelist SE2026</span>
</div>

<div class="card border-0 shadow-sm mb-4 card-jatim">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <small class="fw-semibold"><i class="fas fa-clipboard-list me-1 text-se2026"></i>Prelist SE2026 — <?= number_format($prelist_kpi['total_sls']) ?> SLS</small>
        <span class="badge badge-jatim rounded-pill">38 Kab/Kota</span>
    </div>
    <div class="card-body py-2">
        <div class="row g-2">
            <div class="col-6 col-md-3">
                <small class="text-muted d-block">Kab/Kota</small>
                <span class="fw-bold"><?= number_format($prelist_kpi['total_sls'] > 0 ? count($prelist_komposisi) : 0) ?></span>
            </div>
            <div class="col-6 col-md-3">
                <small class="text-muted d-block">Total KK</small>
                <span class="fw-bold"><?= number_format($prelist_kpi['total_kk']) ?></span>
            </div>
            <div class="col-6 col-md-3">
                <small class="text-muted d-block">Total SLS</small>
                <span class="fw-bold"><?= number_format($prelist_kpi['total_sls']) ?></span>
            </div>
            <div class="col-6 col-md-3">
                <small class="text-muted d-block">UTP</small>
                <span class="fw-bold"><?= number_format($prelist_kpi['total_utp']) ?></span>
            </div>
        </div>
        <hr class="my-2">
        <div class="row g-2">
            <div class="col-4 col-md-2">
                <small class="text-muted d-block">SE2016</small>
                <span class="fw-bold small"><?= number_format($prelist_kpi['total_se2016']) ?></span>
            </div>
            <div class="col-4 col-md-2">
                <small class="text-muted d-block">UB</small>
                <span class="fw-bold small text-danger"><?= number_format($prelist_kpi['total_ub']) ?></span>
            </div>
            <div class="col-4 col-md-2">
                <small class="text-muted d-block">UM</small>
                <span class="fw-bold small text-warning"><?= number_format($prelist_kpi['total_um']) ?></span>
            </div>
            <div class="col-4 col-md-2">
                <small class="text-muted d-block">UMK</small>
                <span class="fw-bold small text-se2026"><?= number_format($prelist_kpi['total_umk']) ?></span>
            </div>
            <div class="col-4 col-md-2">
                <small class="text-muted d-block">PPL</small>
                <span class="fw-bold small text-success"><?= number_format($prelist_kpi['total_ppl']) ?></span>
            </div>
            <div class="col-4 col-md-2">
                <small class="text-muted d-block">PML</small>
                <span class="fw-bold small text-info"><?= number_format($prelist_kpi['total_pml']) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100 card-jatim">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold"><i class="fas fa-chart-bar me-1 text-se2026"></i>Perbandingan SE2016 vs SE2026 per Kab/Kota</small>
                <span class="badge badge-jatim" style="font-size:8px">Prov. Jawa Timur</span>
            </div>
            <div class="card-body">
                <canvas id="chartPrelistPerbandingan" height="320"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100 card-jatim">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold"><i class="fas fa-chart-pie me-1 text-se2026"></i>Komposisi Usaha SE2026</small>
                <span class="badge badge-jatim" style="font-size:8px">Prov. Jawa Timur</span>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="chartPrelistKomposisi" height="260"></canvas>
            </div>
        </div>
    </div>
</div>
<?php
// ─── Anomali Detection Widget ──────────────────────────────────────────────
$totalAnomali = ($prelist_anomali_summary['sls_kk_0'] ?? 0)
              + ($prelist_anomali_summary['sls_utp_0'] ?? 0)
              + ($prelist_anomali_summary['sls_sbr_0'] ?? 0)
              + ($prelist_anomali_summary['sls_muatan_tinggi'] ?? 0);
?>

<?php if ($prelist_imported && !empty($prelist_anomali_summary)): ?>
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm border-start border-danger border-3 card-jember">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold"><i class="fas fa-exclamation-triangle me-1 text-danger"></i>Quality Gates — Indikator Anomali Data</small>
                <span class="badge badge-jember" style="font-size:8px">Kab. Jember</span>
                <span class="badge bg-<?= $totalAnomali > 0 ? 'danger' : 'success' ?> rounded-pill">
                    <?= $totalAnomali ?> anomali
                </span>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body text-center py-2">
                                <small class="text-muted d-block">SLS dgn KK=0</small>
                                <span class="fw-bold fs-5 text-danger"><?= number_format($prelist_anomali_summary['sls_kk_0'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body text-center py-2">
                                <small class="text-muted d-block">UTP=0 padahal ada KK</small>
                                <span class="fw-bold fs-5 text-warning"><?= number_format($prelist_anomali_summary['sls_utp_0'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body text-center py-2">
                                <small class="text-muted d-block">SBR=0 padahal ada KK</small>
                                <span class="fw-bold fs-5 text-warning"><?= number_format($prelist_anomali_summary['sls_sbr_0'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body text-center py-2">
                                <small class="text-muted d-block">Muatan RS >200</small>
                                <span class="fw-bold fs-5 text-danger"><?= number_format($prelist_anomali_summary['sls_muatan_tinggi'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($prelist_anomali)): ?>
                <div class="table-responsive" style="max-height:160px">
                    <table class="table table-sm small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kecamatan</th>
                                <th class="text-center">KK</th>
                                <th class="text-center">UTP</th>
                                <th class="text-center">Muatan</th>
                                <th>Anomali</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prelist_anomali as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['nm_kec']) ?></td>
                                <td class="text-center"><?= number_format($a['jml_kk']) ?></td>
                                <td class="text-center"><?= number_format($a['utp']) ?></td>
                                <td class="text-center"><?= number_format($a['muatan_rs']) ?></td>
                                <td><span class="badge bg-danger bg-opacity-10 text-danger px-2"><?= htmlspecialchars($a['anomali']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100 card-jember">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold"><i class="fas fa-chart-column me-1 text-jember"></i>Muatan per Kecamatan</small>
                <span class="badge badge-jember" style="font-size:8px">Kab. Jember</span>
            </div>
            <div class="card-body">
                <canvas id="chartMuatan" height="280"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100 card-jember">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold"><i class="fas fa-chart-pie me-1 text-jember"></i>Klasifikasi Wilayah</small>
                <span class="badge badge-jember" style="font-size:8px">Kab. Jember</span>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="chartKlasifikasi" height="260"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100 card-jember">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold"><i class="fas fa-chart-pie me-1 text-jember"></i>Beban Pencacah</small>
                <span class="badge badge-jember" style="font-size:8px">Kab. Jember</span>
            </div>
            <div class="card-body">
                <canvas id="chartBebanPencacah" height="220"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100 card-jember">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold"><i class="fas fa-chart-bar me-1 text-jember"></i>Distribusi Muatan Bangunan</small>
                <span class="badge badge-jember" style="font-size:8px">Kab. Jember</span>
            </div>
            <div class="card-body">
                <canvas id="chartBangunan" height="220"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm card-jember">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold"><i class="fas fa-chart-bar me-1 text-jember"></i>Progress Wilayah</small>
                <span class="badge badge-jember" style="font-size:8px">Kab. Jember</span>
            </div>
            <div class="card-body">
                <canvas id="chartProgress" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <small class="fw-semibold"><i class="fas fa-table me-1 text-jember"></i>Progress per Kecamatan</small>
        <span class="badge badge-jember" style="font-size:8px">Kab. Jember</span>
    </div>
    <div class="card-body py-2 px-3">
        <form method="GET" action="" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="dashboard">
            <div class="col-md-4">
                <label class="form-label small mb-0">Filter Kecamatan</label>
                <select name="kdkec" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Semua Kecamatan --</option>
                    <?php foreach ($kecamatan_list as $k): ?>
                        <option value="<?= htmlspecialchars($k['kdkec']) ?>"
                            <?= ($kdkec_filter ?? '') === $k['kdkec'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['nmkec']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-se2026"><i class="fas fa-search"></i></button>
                <?php if (!empty($kdkec_filter)): ?>
                    <a href="?page=dashboard" class="btn btn-sm btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($perbandingan !== null): ?>
<div class="card border-0 shadow-sm mb-3 card-jember">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <small class="fw-semibold"><i class="fas fa-check-double me-1 text-jember"></i>Perbandingan Data: <?= htmlspecialchars($perbandingan['nmkec']) ?></small>
        <span class="badge badge-jember" style="font-size:8px">Kab. Jember</span>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center py-3">
                        <small class="text-muted d-block">Referensi (master_sls)</small>
                        <span class="fw-bold fs-4"><?= number_format($perbandingan['master_count']) ?></span>
                        <small class="text-muted d-block">SLS</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center py-3">
                        <small class="text-muted d-block">Database (sipw_import)</small>
                        <span class="fw-bold fs-4"><?= number_format($perbandingan['sipw_count']) ?></span>
                        <small class="text-muted d-block">SLS</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center py-3">
                        <small class="text-muted d-block">Selisih</small>
                        <span class="fw-bold fs-4 text-<?= $perbandingan['selisih'] === 0 ? 'success' : 'danger' ?>">
                            <?= $perbandingan['selisih'] >= 0 ? '+' : '' ?><?= number_format($perbandingan['selisih']) ?>
                        </span>
                        <small class="text-muted d-block">SLS</small>
                    </div>
                </div>
            </div>
        </div>
    <div class="card-body">
        <div class="mb-2"><span class="fw-semibold">Kecamatan:</span> <?= htmlspecialchars($perbandingan['nmkec']) ?></div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center py-3">
                        <small class="text-muted d-block">Referensi (master_sls)</small>
                        <span class="fw-bold fs-4"><?= number_format($perbandingan['master_count']) ?></span>
                        <small class="text-muted d-block">SLS</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center py-3">
                        <small class="text-muted d-block">Database (sipw_import)</small>
                        <span class="fw-bold fs-4"><?= number_format($perbandingan['sipw_count']) ?></span>
                        <small class="text-muted d-block">SLS</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center py-3">
                        <small class="text-muted d-block">Selisih</small>
                        <span class="fw-bold fs-4 text-<?= $perbandingan['selisih'] === 0 ? 'success' : 'danger' ?>">
                            <?= $perbandingan['selisih'] >= 0 ? '+' : '' ?><?= number_format($perbandingan['selisih']) ?>
                        </span>
                        <small class="text-muted d-block">SLS</small>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($perbandingan['selisih'] !== 0): ?>
        <div class="alert alert-<?= $perbandingan['selisih'] > 0 ? 'warning' : 'info' ?> py-2 small mb-0">
            <i class="fas fa-<?= $perbandingan['selisih'] > 0 ? 'exclamation-triangle' : 'info-circle' ?> me-1"></i>
            <?php if ($perbandingan['selisih'] > 0): ?>
                Terdapat <strong><?= number_format($perbandingan['selisih']) ?> SLS</strong> yang ada di referensi (master_sls) tetapi belum masuk database (sipw_import).
            <?php else: ?>
                Database memiliki <strong><?= number_format(abs($perbandingan['selisih'])) ?> SLS</strong> lebih banyak dari referensi.
            <?php endif; ?>
        </div>

            <?php if (!empty($perbandingan['missing'])): ?>
            <div class="mt-2">
                <small class="fw-semibold">Daftar SLS yang belum masuk database:</small>
                <div class="table-responsive" style="max-height:200px">
                    <table class="table table-sm mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Kode SLS (16 digit)</th>
                                <th>Nama SLS</th>
                                <th>Desa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($perbandingan['missing'] as $m): ?>
                            <tr>
                                <td class="text-monospace small"><?= htmlspecialchars($m['kode'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($m['sls'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($m['desa'] ?? '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        <?php else: ?>
        <div class="alert alert-success py-2 small mb-0">
            <i class="fas fa-check-circle me-1"></i>
            Semua SLS di kecamatan ini sudah sesuai antara referensi dan database.
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ─── Petugas Integration Section ──────────────────────────────────── -->
<?php if (!empty($petugas_stats) || !empty($assignment_summary)): ?>
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="section-label-se2026 mb-3">
            <i class="fas fa-users-cog"></i> Manajemen Petugas & Assignment
            <span class="badge bg-se2026 ms-1">Integrasi Data</span>
        </div>
    </div>

    <!-- Petugas Stats Cards -->
    <?php if (!empty($petugas_stats['all_roles'])): ?>
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold"><i class="fas fa-users me-1 text-se2026"></i>Statistik Semua Petugas (by Role)</small>
                <a href="?page=dashboard&sub=petugas" class="btn btn-sm btn-outline-se2026">
                    <i class="fas fa-external-link-alt me-1"></i>Buka Halaman Petugas
                </a>
            </div>
            <div class="card-body py-2">
                <div class="row g-2">
                    <?php foreach ($petugas_stats['all_roles'] as $pr): 
                        $roleLabel = $roleLabels[$pr['role']] ?? ucfirst($pr['role']);
                        $badgeClass = match($pr['role']) {
                            'admin' => 'danger', 'operator' => 'secondary', 'pegawai' => 'primary',
                            'pcl' => 'success', 'pml' => 'warning text-dark', 'task_force' => 'info',
                            'mitra' => 'dark', 'panitia' => 'purple',
                            default => 'secondary'
                        };
                    ?>
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body text-center py-2">
                                <small class="text-muted d-block"><?= htmlspecialchars($roleLabel) ?></small>
                                <div class="d-flex justify-content-center gap-2 small">
                                    <span class="text-success"><?= number_format($pr['aktif']) ?> aktif</span>
                                    <span class="text-muted">/</span>
                                    <span><?= number_format($pr['total']) ?> total</span>
                                </div>
                                <small class="badge bg-<?= $badgeClass ?> mt-1"><?= strtoupper($pr['role']) ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Petugas Lapangan (PCL/PML/TF) Summary -->
    <?php if (!empty($petugas_stats['lapangan_roles'])): ?>
    <div class="col-12">
        <div class="card border-0 shadow-sm mb-3 border-start border-success border-3">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold"><i class="fas fa-people-carry-box me-1 text-success"></i>Petugas Lapangan (PCL / PML / Task Force)</small>
                <a href="?page=dashboard&sub=petugas-lapangan" class="btn btn-sm btn-outline-success">
                    <i class="fas fa-external-link-alt me-1"></i>Buka Halaman Petugas Lapangan
                </a>
            </div>
            <div class="card-body py-2">
                <div class="row g-2">
                    <?php foreach ($petugas_stats['lapangan_roles'] as $lr): 
                        $roleLabel = $roleLabels[$lr['role']] ?? ucfirst($lr['role']);
                        $badgeClass = match($lr['role']) {
                            'pcl' => 'success', 'pml' => 'warning text-dark', 'task_force' => 'info',
                            default => 'secondary'
                        };
                    ?>
                    <div class="col-6 col-md-4">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body text-center py-2">
                                <small class="text-muted d-block"><?= htmlspecialchars($roleLabel) ?></small>
                                <span class="fw-bold fs-4 text-<?= $lr['role'] === 'pcl' ? 'success' : ($lr['role'] === 'pml' ? 'warning' : 'info') ?>">
                                    <?= number_format($lr['aktif']) ?>
                                </span>
                                <div class="small text-muted">dari <?= number_format($lr['total']) ?> total</div>
                                <small class="badge bg-<?= $badgeClass ?> mt-1"><?= strtoupper($lr['role']) ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Assignment Summary -->
<?php if (!empty($assignment_summary)): ?>
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm border-start border-info border-3">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold"><i class="fas fa-tasks me-1 text-info"></i>Ringkasan Assignment Petugas</small>
                <a href="?page=dashboard&sub=assignment" class="btn btn-sm btn-outline-info">
                    <i class="fas fa-external-link-alt me-1"></i>Buka Halaman Assignment
                </a>
            </div>
            <div class="card-body py-2">
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body text-center py-2">
                                <small class="text-muted d-block">Total SLS (Assignable)</small>
                                <span class="fw-bold fs-4 text-primary"><?= number_format($assignment_summary['total_sls'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body text-center py-2">
                                <small class="text-muted d-block">Total Non-SLS</small>
                                <span class="fw-bold fs-4 text-info"><?= number_format($assignment_summary['total_non_sls'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body text-center py-2">
                                <small class="text-muted d-block">Total Gabungan</small>
                                <span class="fw-bold fs-4 text-se2026"><?= number_format($assignment_summary['total_gabungan'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body text-center py-2">
                                <small class="text-muted d-block">Sudah Di-Assign</small>
                                <span class="fw-bold fs-4 text-success"><?= number_format($assignment_summary['total_assigned'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body text-center py-2">
                                <small class="text-muted d-block">Status: Belum</small>
                                <span class="fw-bold fs-5 text-warning"><?= number_format($assignment_summary['status_belum'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body text-center py-2">
                                <small class="text-muted d-block">Status: Proses</small>
                                <span class="fw-bold fs-5 text-info"><?= number_format($assignment_summary['status_proses'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body text-center py-2">
                                <small class="text-muted d-block">Status: Selesai</small>
                                <span class="fw-bold fs-5 text-success"><?= number_format($assignment_summary['status_selesai'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body text-center py-2">
                                <small class="text-muted d-block">PCL yang Di-Assign</small>
                                <span class="fw-bold fs-5 text-success"><?= number_format($assignment_summary['pcl_assigned'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body text-center py-2">
                                <small class="text-muted d-block">PML yang Di-Assign</small>
                                <span class="fw-bold fs-5 text-warning"><?= number_format($assignment_summary['pml_assigned'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body text-center py-2">
                                <small class="text-muted d-block">TF yang Di-Assign</small>
                                <span class="fw-bold fs-5 text-info"><?= number_format($assignment_summary['tf_assigned'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Pegawai Scope (Kecamatan Tugas) -->
<?php if (!empty($petugas_stats['pegawai_scope'])): ?>
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm border-start border-primary border-3">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold"><i class="fas fa-map-marker-alt me-1 text-primary"></i>Pegawai dengan Scope Kecamatan (1:1)</small>
            </div>
            <div class="card-body py-2">
                <div class="table-responsive" style="max-height:200px">
                    <table class="table table-sm table-hover mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Kecamatan Tugas</th>
                                <th>Kode (7-digit)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($petugas_stats['pegawai_scope'] as $ps): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($ps['nama_lengkap'] ?? $ps['username']) ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars($ps['email'] ?? '-') ?></small></td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($ps['nmkec'] ?? '-') ?></span></td>
                                <td><small class="text-muted text-monospace"><?= htmlspecialchars($ps['kecamatan_tugas'] ?? '-') ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3 card-jember">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center gap-2 small">
            <i class="fas fa-info-circle text-jember"></i>
            <span>Data master resmi Semester 1 2025: <strong>16.772 SLS</strong> dari <strong>248 desa</strong> di <strong>31 kecamatan</strong>. Sumber: <code>msubsls_25_2_3509.xlsx</code> dan <code>mfd_25_2_3509.xlsx</code>. <span class="badge badge-jember" style="font-size:8px">Kab. Jember</span></span>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm card-jember">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <small class="fw-semibold"><i class="fas fa-table me-1 text-jember"></i>Progress per Kecamatan</small>
        <span class="badge badge-jember" style="font-size:8px">Kab. Jember</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 small datatable">
                <thead class="table-light">
                    <tr>
                        <th>Kecamatan</th>
                        <th class="text-center">Total SLS</th>
                        <th class="text-center">Total Muatan</th>
                        <th class="text-center">Proses</th>
                        <th class="text-center">Selesai</th>
                        <th class="text-center">Progress</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($progress_wilayah as $row): ?>
                        <?php $progress = $row['progress'] ?? 0; ?>
                        <tr>
                            <td><?= htmlspecialchars($row['label']) ?></td>
                            <td class="text-center"><?= number_format($row['total_sls']) ?></td>
                            <td class="text-center"><?= number_format($row['total_muatan']) ?></td>
                            <td class="text-center"><?= number_format($row['proses']) ?></td>
                            <td class="text-center"><?= number_format($row['selesai']) ?></td>
                            <td class="text-center" style="min-width:120px">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:6px">
                                        <div class="progress-bar bg-success" style="width:<?= $progress ?>%"></div>
                                    </div>
                                    <small class="text-muted" style="width:40px"><?= $progress ?>%</small>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($progress_wilayah)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data. Import SIPW terlebih dahulu.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($prelist_imported && !empty($prelist_map_kec)): ?>
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm card-jember">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold"><i class="fas fa-map me-1 text-jember"></i>Peta Sebaran Muatan per Kecamatan</small>
                <span class="d-flex align-items-center gap-2"><span class="badge badge-jember" style="font-size:8px">Kab. Jember</span><small class="text-muted">Bubble size = total muatan RS</small></span>
            </div>
            <div class="card-body p-0">
                <div id="mapPrelist" style="height:420px; border-radius:0 0 var(--bs-card-border-radius) var(--bs-card-border-radius);"></div>
            </div>
        </div>
    </div>
</div>
<script>
var mapKecamatan = <?= json_encode($prelist_map_kec, JSON_UNESCAPED_UNICODE) ?>;
</script>
<?php endif; ?>

<!-- ─── Legenda Wilayah ─────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2 px-3">
        <div class="d-flex flex-wrap align-items-center gap-3 small">
            <span class="fw-semibold me-2"><i class="fas fa-info-circle me-1"></i>Legenda Wilayah:</span>
            <span><span class="badge badge-jatim me-1">&nbsp;</span> Provinsi Jawa Timur</span>
            <span><span class="badge badge-jember me-1">&nbsp;</span> Kabupaten Jember</span>
            <span><span class="badge badge-lain me-1">&nbsp;</span> Kabupaten Lain</span>
            <span class="text-muted ms-3"><i class="far fa-circle text-jember me-1"></i>Border biru = data Jember</span>
            <span class="text-muted"><i class="far fa-circle text-se2026 me-1"></i>Border oranye = data Jatim</span>
        </div>
    </div>
</div>

<script>
var chartData = {
    muatan: {
        labels: <?= json_encode(array_column($muatan_per_kec, 'label')) ?>,
        datasets: [{
            label: 'Muatan',
            data: <?= json_encode(array_map('intval', array_column($muatan_per_kec, 'total_muatan'))) ?>,
            backgroundColor: 'rgba(244, 123, 32, 0.7)',
            borderColor: 'rgba(244, 123, 32, 1)',
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    klasifikasi: {
        labels: ['Perkotaan (Urban)', 'Pedesaan (Rural)'],
        data: [
            <?= (int) ($stats['total_sls_urban'] ?? 0) ?>,
            <?= (int) ($stats['total_sls_rural'] ?? 0) ?>
        ],
        colors: ['rgba(244, 123, 32, 0.8)', 'rgba(25, 135, 84, 0.8)'],
    },
    bangunan: {
        labels: ['BSTT', 'BSBTT', 'BSTTK', 'BKU'],
        data: [
            <?= (int) ($stats['total_bstt'] ?? 0) ?>,
            <?= (int) ($stats['total_bsbtt'] ?? 0) ?>,
            <?= (int) ($stats['total_bsttk'] ?? 0) ?>,
            <?= (int) ($stats['total_bku'] ?? 0) ?>
        ],
    },
    bebanPencacah: {
        labels: <?= json_encode(array_column($beban_pencacah, 'username')) ?>,
        datasets: [{
            label: 'Total Assign',
            data: <?= json_encode(array_map('intval', array_column($beban_pencacah, 'total_assign'))) ?>,
            backgroundColor: [
                'rgba(25, 135, 84, 0.8)',
                'rgba(244, 123, 32, 0.8)',
                'rgba(255, 193, 7, 0.8)',
                'rgba(111, 66, 193, 0.8)',
                'rgba(220, 53, 69, 0.8)',
                'rgba(13, 202, 240, 0.8)',
            ],
            borderWidth: 0,
        }]
    },
    progress: {
        labels: <?= json_encode(array_column($progress_wilayah, 'label')) ?>,
        proses: <?= json_encode(array_map('intval', array_column($progress_wilayah, 'proses'))) ?>,
        selesai: <?= json_encode(array_map('intval', array_column($progress_wilayah, 'selesai'))) ?>,
    },
    prelist: <?php if ($prelist_imported && !empty($prelist_perbandingan)): ?>{
        perbandingan: {
            labels: <?= json_encode(array_reverse(array_column($prelist_perbandingan, 'nm_kabkota'))) ?>,
            se2016: <?= json_encode(array_reverse(array_map('intval', array_column($prelist_perbandingan, 'se2016')))) ?>,
            se2026: <?= json_encode(array_reverse(array_map('intval', array_column($prelist_perbandingan, 'se2026')))) ?>,
        },
        komposisi: {
            labels: ['Usaha Besar (UB)', 'Usaha Menengah (UM)', 'Usaha Mikro Kecil (UMK)'],
            data: [
                <?= (int) ($prelist_kpi['total_ub'] ?? 0) ?>,
                <?= (int) ($prelist_kpi['total_um'] ?? 0) ?>,
                <?= (int) ($prelist_kpi['total_umk'] ?? 0) ?>,
            ],
            colors: ['#dc3545', '#ffc107', '#F47B20'],
        },
    }
<?php else: ?>
    null
<?php endif; ?>,
};
</script>
