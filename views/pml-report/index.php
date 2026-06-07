<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="fas fa-clipboard-list me-2"></i>Laporan Statistik PML</h5>
    <div class="d-flex gap-2">
        <a href="?page=dashboard&sub=pml-report&action=export&periode=<?= htmlspecialchars($periode) ?>" class="btn btn-outline-success btn-sm">
            <i class="fas fa-file-excel me-1"></i>Export Excel
        </a>
    </div>
</div>

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
