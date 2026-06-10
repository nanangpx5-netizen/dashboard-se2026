<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="fas fa-clipboard-list me-2"></i>Laporan PML</h5>
    <div class="d-flex gap-2">
        <?php if ($current_user['role'] !== 'pml'): ?>
        <a href="?page=dashboard&sub=pml-report&action=export&periode=<?= htmlspecialchars($periode) ?>" class="btn btn-outline-success btn-sm">
            <i class="fas fa-file-excel me-1"></i>Export Excel
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($current_user['role'] === 'pml'): ?>
<!-- ─── PML Report Submit Form ─────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2">
                <h6 class="mb-0 fw-semibold"><i class="fas fa-paper-plane me-1 text-se2026"></i>Kirim Laporan Bulanan</h6>
            </div>
            <div class="card-body">
                <div id="pmlReportAlert" class="d-none"></div>

                <form id="formPmlReport" method="post">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small">Periode Laporan</label>
                            <input type="month" class="form-control form-control-sm" id="inputPeriode" name="periode" value="<?= htmlspecialchars($periode) ?>" max="<?= date('Y-m') ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small">Catatan (opsional)</label>
                            <textarea class="form-control form-control-sm" id="inputCatatan" name="catatan" rows="2" maxlength="500" placeholder="Kendala lapangan, catatan khusus, dll."><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <?php if ($pml_assignment_count > 0 && !$pml_existing_report): ?>
                    <div class="row g-2 mt-2">
                        <div class="col-md-3 col-6">
                            <div class="p-2 bg-light rounded text-center">
                                <small class="text-muted d-block">Total Alokasi</small>
                                <span class="fw-bold fs-5"><?= number_format((int)($pml_report_data['total'] ?? 0)) ?></span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-2 bg-light rounded text-center">
                                <small class="text-muted d-block">Selesai</small>
                                <span class="fw-bold fs-5 text-success"><?= number_format((int)($pml_report_data['selesai'] ?? 0)) ?></span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-2 bg-light rounded text-center">
                                <small class="text-muted d-block">Proses</small>
                                <span class="fw-bold fs-5 text-warning"><?= number_format((int)($pml_report_data['proses'] ?? 0)) ?></span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-2 bg-light rounded text-center">
                                <small class="text-muted d-block">Belum</small>
                                <span class="fw-bold fs-5 text-secondary"><?= number_format((int)($pml_report_data['belum'] ?? 0)) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="mt-3">
                        <?php if ($pml_existing_report): ?>
                        <div class="alert alert-info py-2 mb-0 small">
                            <i class="fas fa-check-circle me-1"></i>Laporan periode <strong><?= htmlspecialchars($periode) ?></strong> sudah dikirim pada <?= htmlspecialchars($pml_existing_report['submitted_at']) ?>.
                            <a href="?page=dashboard&sub=pml-report" class="alert-link">Pilih periode lain</a>.
                        </div>
                        <?php elseif ($pml_assignment_count === 0): ?>
                        <div class="alert alert-warning py-2 mb-0 small">
                            <i class="fas fa-exclamation-triangle me-1"></i>Anda belum memiliki alokasi SLS. Hubungi admin untuk penugasan sebelum mengirim laporan.
                        </div>
                        <?php else: ?>
                        <button type="submit" class="btn btn-se2026 btn-sm" id="btnSubmitReport">
                            <i class="fas fa-paper-plane me-1"></i>Kirim Laporan
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnLoadingReport" style="display:none" disabled>
                            <span class="spinner-border spinner-border-sm me-1"></span>Mengirim...
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2">
                <h6 class="mb-0 fw-semibold"><i class="fas fa-info-circle me-1 text-se2026"></i>Informasi</h6>
            </div>
            <div class="card-body small">
                <ul class="mb-0 ps-3">
                    <li class="mb-1">Laporan dikirim <strong>1 kali per bulan</strong>.</li>
                    <li class="mb-1">Data progres SLS diambil otomatis dari sistem assignment.</li>
                    <li class="mb-1">Pastikan semua data SLS sudah di-update sebelum mengirim.</li>
                    <li>Catatan maksimal 500 karakter.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($current_user['role'] !== 'pml'): ?>
<!-- ─── Admin/Operator KPI Cards ──────────────────────── -->
<div class="row g-2 mb-3">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm text-center py-2 h-100 border-start border-primary border-3">
            <small class="text-muted">Total PML Aktif</small>
            <span class="fw-bold fs-4 text-primary"><?= number_format($stats['total_pml'] ?? 0) ?></span>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm text-center py-2 h-100 border-start border-success border-3">
            <small class="text-muted">Dengan Alokasi SLS</small>
            <span class="fw-bold fs-4 text-success"><?= number_format($stats['with_assignment'] ?? 0) ?></span>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm text-center py-2 h-100 border-start border-warning border-3">
            <small class="text-muted">SLS Selesai / Total</small>
            <span class="fw-bold fs-4 text-warning"><?= number_format($stats['selesai'] ?? 0) ?><small class="fs-6">/<?= number_format($stats['total_sls'] ?? 0) ?></small></span>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm text-center py-2 h-100 border-start border-secondary border-3">
            <small class="text-muted">Tanpa Alokasi</small>
            <span class="fw-bold fs-4 text-secondary"><?= number_format($stats['without_assignment'] ?? 0) ?></span>
        </div>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-3">
        <label class="form-label small mb-0">Kecamatan</label>
        <select class="form-select form-select-sm" id="filterKdkec" onchange="reloadStats()">
            <option value="">Semua Kecamatan</option>
            <?php foreach ($kecamatan as $k): ?>
            <option value="<?= htmlspecialchars($k['kdkec']) ?>"><?= htmlspecialchars($k['nmkec']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small mb-0">Status</label>
        <select class="form-select form-select-sm" id="filterStatus" onchange="reloadTable()">
            <option value="">Semua PML</option>
            <option value="tanpa_alokasi">Tanpa Alokasi</option>
            <option value="sudah_lapor">Sudah Lapor</option>
            <option value="belum_lapor">Belum Lapor</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-0">Periode</label>
        <input type="month" class="form-control form-control-sm" id="filterPeriode" value="<?= htmlspecialchars($periode) ?>" onchange="reloadStats()">
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <input type="text" class="form-control form-control-sm" id="filterSearch" placeholder="Cari PML..." onkeyup="debounceSearch()">
    </div>
    <div class="col-md-2 d-flex align-items-end gap-1">
        <button class="btn btn-sm btn-se2026" onclick="reloadTable()"><i class="fas fa-search"></i></button>
        <button class="btn btn-sm btn-outline-secondary" onclick="resetFilters()">Reset</button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 small" id="tablePmlReport">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px">No</th>
                        <th>Nama PML</th>
                        <th>Email</th>
                        <th>Kecamatan Bertugas</th>
                        <th>Desa Bertugas</th>
                        <th class="text-center">Alokasi</th>
                        <th class="text-center">Selesai</th>
                        <th class="text-center">Proses</th>
                        <th class="text-center">Belum</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="11" class="text-center text-muted py-4">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Detail SLS per PML -->
<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-semibold"><i class="fas fa-list me-1"></i>Detail SLS — <span id="detailPmlName">-</span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>SLS</th>
                                <th>Desa</th>
                                <th>Kecamatan</th>
                                <th>Status</th>
                                <th>Mulai</th>
                                <th>Selesai</th>
                            </tr>
                        </thead>
                        <tbody id="detailBody">
                            <tr><td colspan="6" class="text-center text-muted py-3">Memuat...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
