<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="fas fa-gauge-high me-2"></i>Dashboard SE2026</h5>
    <small class="text-muted"><?= date('d F Y H:i') ?> WIB</small>
</div>

<div class="row g-2 g-md-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="flex-shrink-0 text-primary fs-3"><i class="fas fa-map"></i></div>
                <div class="min-w-0">
                    <small class="text-muted d-block text-truncate">Kecamatan</small>
                    <span class="fw-bold fs-4"><?= number_format($stats['total_kecamatan']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="flex-shrink-0 text-info fs-3"><i class="fas fa-location-dot"></i></div>
                <div class="min-w-0">
                    <small class="text-muted d-block text-truncate">Desa</small>
                    <span class="fw-bold fs-4"><?= number_format($stats['total_desa']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="flex-shrink-0 text-secondary fs-3"><i class="fas fa-database"></i></div>
                <div class="min-w-0">
                    <small class="text-muted d-block text-truncate">Total SLS</small>
                    <span class="fw-bold fs-4"><?= number_format($stats['total_sls']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="flex-shrink-0 text-success fs-3"><i class="fas fa-users"></i></div>
                <div class="min-w-0">
                    <small class="text-muted d-block text-truncate">Total KK</small>
                    <span class="fw-bold fs-4"><?= number_format($stats['total_kk']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="flex-shrink-0 text-warning fs-3"><i class="fas fa-store"></i></div>
                <div class="min-w-0">
                    <small class="text-muted d-block text-truncate">Total Usaha</small>
                    <span class="fw-bold fs-4"><?= number_format($stats['total_usaha']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="flex-shrink-0 text-danger fs-3"><i class="fas fa-weight"></i></div>
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
        <div class="card border-0 shadow-sm h-100 border-start border-success border-3">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="flex-shrink-0 text-success fs-3"><i class="fas fa-user-check"></i></div>
                <div>
                    <small class="text-muted d-block">Pencacah (PCL)</small>
                    <span class="fw-bold fs-4"><?= number_format($stats['total_pencacah']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-4">
        <div class="card border-0 shadow-sm h-100 border-start border-warning border-3">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="flex-shrink-0 text-warning fs-3"><i class="fas fa-user-tie"></i></div>
                <div>
                    <small class="text-muted d-block">Pengawas (PML)</small>
                    <span class="fw-bold fs-4"><?= number_format($stats['total_pengawas']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-4">
        <div class="card border-0 shadow-sm h-100 border-start border-info border-3">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="flex-shrink-0 text-info fs-3"><i class="fas fa-people-group"></i></div>
                <div>
                    <small class="text-muted d-block">Task Force</small>
                    <span class="fw-bold fs-4"><?= number_format($stats['total_task_force']) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2">
                <small class="fw-semibold"><i class="fas fa-chart-column me-1 text-primary"></i>Muatan per Kecamatan</small>
            </div>
            <div class="card-body">
                <canvas id="chartMuatan" height="280"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2">
                <small class="fw-semibold"><i class="fas fa-chart-pie me-1 text-success"></i>Beban Pencacah</small>
            </div>
            <div class="card-body">
                <canvas id="chartBebanPencacah" height="280"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2">
                <small class="fw-semibold"><i class="fas fa-chart-bar me-1 text-info"></i>Progress Wilayah</small>
            </div>
            <div class="card-body">
                <canvas id="chartProgress" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <small class="fw-semibold"><i class="fas fa-table me-1 text-secondary"></i>Progress per Kecamatan</small>
        <small class="text-muted"><?= count($progress_wilayah) ?> kecamatan</small>
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
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                <?php if (!empty($kdkec_filter)): ?>
                    <a href="?page=dashboard" class="btn btn-sm btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($perbandingan !== null): ?>
<div class="card border-0 shadow-sm mb-3 border-start border-<?= $perbandingan['selisih'] === 0 ? 'success' : 'danger' ?> border-3">
    <div class="card-header bg-white py-2">
        <small class="fw-semibold"><i class="fas fa-check-double me-1 text-secondary"></i>Perbandingan Data Kecamatan: <?= htmlspecialchars($perbandingan['nmkec']) ?></small>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center py-3">
                        <small class="text-muted d-block">Referensi (master_sls)</small>
                        <span class="fw-bold fs-4"><?= number_format($perbandingan['master_count']) ?></span>
                        <small class="text-muted d-block">SLS</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center py-3">
                        <small class="text-muted d-block">Database (sipw_import)</small>
                        <span class="fw-bold fs-4"><?= number_format($perbandingan['sipw_count']) ?></span>
                        <small class="text-muted d-block">SLS</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
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
            <div class="col-md-3">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center py-3">
                        <small class="text-muted d-block">Duplikat (kode)</small>
                        <span class="fw-bold fs-4 text-<?= $perbandingan['dup_kode'] > 0 ? 'warning' : 'success' ?>">
                            <?= number_format($perbandingan['dup_kode']) ?>
                        </span>
                        <small class="text-muted d-block"><?= $perbandingan['dup_rows'] ?> baris duplikat</small>
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
                <small class="fw-semibold">Contoh SLS yang belum masuk database:</small>
                <div class="table-responsive" style="max-height:200px">
                    <table class="table table-sm mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>SLS</th>
                                <th>Desa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($perbandingan['missing'] as $m): ?>
                            <tr>
                                <td class="text-muted"><?= htmlspecialchars($m['kode'] ?? '-') ?></td>
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

        <?php if ($perbandingan['dup_kode'] > 0): ?>
        <div class="alert alert-warning py-2 small mt-2 mb-0">
            <i class="fas fa-copy me-1"></i>
            Terdapat <strong><?= number_format($perbandingan['dup_kode']) ?> kode SLS duplikat</strong>
            (<?= number_format($perbandingan['dup_rows']) ?> baris duplikat) dalam file referensi (rekap-sls.xlsx).
            Database hanya menyimpan 1 baris per kode unik.
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3 border-start border-info border-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center gap-2 small">
            <i class="fas fa-info-circle text-info"></i>
            <span>File referensi <strong>rekap-sls.xlsx</strong> memiliki <strong>16.772 baris</strong> dengan <strong>234 baris duplikat</strong> (kode SLS sama). Database hanya menyimpan <strong>16.538 SLS unik</strong> — duplikat sudah otomatis dibersihkan saat import.</span>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <small class="fw-semibold"><i class="fas fa-table me-1 text-secondary"></i>Progress per Kecamatan</small>
        <small class="text-muted"><?= count($progress_wilayah) ?> kecamatan</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 small datatable">
                <thead class="table-light">
                    <tr>
                        <th>Kecamatan</th>
                        <th class="text-center">Total SLS</th>
                        <th class="text-center">Total Muatan</th>
                        <th class="text-center">Assign</th>
                        <th class="text-center">Proses</th>
                        <th class="text-center">Selesai</th>
                        <th class="text-center">Progress</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($progress_wilayah as $row): ?>
                        <?php $progress = $row['total_sls'] > 0
                            ? round(($row['selesai'] / $row['total_sls']) * 100, 1) : 0; ?>
                        <tr>
                            <td><?= htmlspecialchars($row['label']) ?></td>
                            <td class="text-center"><?= number_format($row['total_sls']) ?></td>
                            <td class="text-center"><?= number_format($row['total_muatan']) ?></td>
                            <td class="text-center"><?= number_format($row['assigned']) ?></td>
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
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data. Import SIPW terlebih dahulu.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
            backgroundColor: 'rgba(13, 110, 253, 0.7)',
            borderColor: 'rgba(13, 110, 253, 1)',
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    bebanPencacah: {
        labels: <?= json_encode(array_column($beban_pencacah, 'username')) ?>,
        datasets: [{
            label: 'Total Assign',
            data: <?= json_encode(array_map('intval', array_column($beban_pencacah, 'total_assign'))) ?>,
            backgroundColor: [
                'rgba(25, 135, 84, 0.8)',
                'rgba(13, 110, 253, 0.8)',
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
        assigned: <?= json_encode(array_map('intval', array_column($progress_wilayah, 'assigned'))) ?>,
        proses: <?= json_encode(array_map('intval', array_column($progress_wilayah, 'proses'))) ?>,
        selesai: <?= json_encode(array_map('intval', array_column($progress_wilayah, 'selesai'))) ?>,
    }
};
</script>
