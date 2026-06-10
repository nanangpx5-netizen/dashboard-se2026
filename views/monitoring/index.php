<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="fas fa-monitor-waveform me-2"></i>Monitoring Wilayah</h5>
    <small class="text-muted" id="clockDisplay"><?= date('d F Y H:i') ?> WIB</small>
</div>

<div class="row g-2 mb-4">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <small class="text-muted">Total SLS</small>
                <h4 class="fw-bold mt-1 mb-0" id="statTotalSls"><?= number_format($summary['total_sls'] ?? 0) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <small class="text-muted">Sudah Assign</small>
                <h4 class="fw-bold mt-1 mb-0 text-success" id="statAssigned"><?= number_format($summary['assigned_sls'] ?? 0) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <small class="text-muted">Proses</small>
                <h4 class="fw-bold mt-1 mb-0 text-warning" id="statProses"><?= number_format($summary['progress_sls'] ?? 0) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <small class="text-muted">Selesai</small>
                <h4 class="fw-bold mt-1 mb-0 text-success" id="statSelesai"><?= number_format($summary['completed_sls'] ?? 0) ?></h4>
            </div>
        </div>
    </div>
</div>

    <div class="row g-2 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-se2026 shadow-sm h-100">
                <div class="card-body py-3">
                    <small class="text-muted">Total Assignment FASIH</small>
                    <h4 class="fw-bold mt-1 mb-0 text-se2026" id="statTotalFasih">
                        <?= number_format($fasih_summary['total_fasih'] ?? 0) ?>
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-se2026 shadow-sm h-100">
                <div class="card-body py-3">
                    <small class="text-muted">Total FCC (Keluarga)</small>
                    <h4 class="fw-bold mt-1 mb-0 text-se2026" id="statTotalFasihKK">
                        <?= number_format($fasih_summary['fasih_kk'] ?? 0) ?>
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-se2026 shadow-sm h-100">
                <div class="card-body py-3">
                    <small class="text-muted">Total UMK</small>
                    <h4 class="fw-bold mt-1 mb-0 text-se2026" id="statTotalFasihUMK">
                        <?= number_format($fasih_summary['fasih_umk'] ?? 0) ?>
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-se2026 shadow-sm h-100">
                <div class="card-body py-3">
                    <small class="text-muted">Open PBI</small>
                    <h4 class="fw-bold mt-1 mb-0 text-se2026" id="statTotalPBI">
                        <?= number_format($fasih_summary['sls_pbi'] ?? 0) ?> SLS / <?= number_format($fasih_summary['kk_pbi'] ?? 0) ?> KK
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Pairing Progress Widget ─────────────────────── -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <small class="fw-semibold"><i class="fas fa-link me-1 text-se2026"></i>LK Pairing Progress</small>
            <button class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="loadPairingProgress()" title="Refresh"><i class="fas fa-sync-alt"></i></button>
        </div>
        <div class="card-body py-2" id="pairingProgressBody">
            <div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Memuat data pairing...</div>
        </div>
    </div>

    <!-- ─── 4 PPL Missing Alert ─────────────────────────── -->
    <div id="missingPplAlert" class="d-none mb-3"></div>

    <!-- ─── Pairing Distribution per Kecamatan ─────────── -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <small class="fw-semibold"><i class="fas fa-layer-group me-1 text-se2026"></i>Distribusi Pairing per Kecamatan</small>
            <button class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="loadPairingPerKec()" title="Refresh"><i class="fas fa-sync-alt"></i></button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height:240px">
                <table class="table table-sm table-hover mb-0" style="font-size:11px">
                    <thead class="bg-light sticky-top">
                        <tr>
                            <th>Kecamatan</th>
                            <th class="text-center">Subsls</th>
                            <th class="text-center">Muatan</th>
                            <th class="text-center">PPL</th>
                            <th class="text-center">PML</th>
                        </tr>
                    </thead>
                    <tbody id="pairingKecTable"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2 mb-3">
        <h6 class="mb-0 fw-semibold"><i class="fas fa-map me-1 text-se2026"></i>Kecamatan — Status Assign</h6>
        <button class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="loadKecamatanSummary()" title="Refresh"><i class="fas fa-sync-alt"></i></button>
        <div class="ms-auto small text-muted">
            <i class="fas fa-layer-group me-1"></i><span id="kecTotalAssigned">0</span> dari <span id="kecTotal">0</span> kecamatan ter-assign
        </div>
    </div>

<div class="row g-2 mb-4" id="kecamatanCards">
    <div class="col-12 text-center text-muted py-4">
        <span class="spinner-border spinner-border-sm me-2"></span>Memuat data kecamatan...
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <small class="fw-semibold" id="desaSummaryTitle"><i class="fas fa-tree me-1 text-se2026"></i>Desa — Rincian per Kecamatan</small>
                <button class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="loadDesaSummary()" title="Refresh"><i class="fas fa-sync-alt"></i></button>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <select id="desaKdkecFilter" class="form-select form-select-sm" onchange="loadDesaSummary()">
                        <option value="">-- Pilih Kecamatan --</option>
                        <?php foreach ($kec_summary as $k): ?>
                            <option value="<?= htmlspecialchars($k['kdkec']) ?>"><?= htmlspecialchars($k['nmkec']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="desaSummaryContainer" class="small" style="max-height:320px; overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0" style="font-size:11px">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th>Desa</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Assign</th>
                                <th class="text-center">Selesai</th>
                                <th>Progres</th>
                                <th class="text-center">St</th>
                            </tr>
                        </thead>
                        <tbody id="desaSummaryBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <ul class="nav nav-tabs card-header-tabs" id="slsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-sls-btn" data-bs-toggle="tab" data-bs-target="#tabSls" type="button" role="tab">
                            <i class="fas fa-database me-1"></i>SLS Assign
                            <span class="badge bg-secondary ms-1" id="slsBadgeCount">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-nonsls-btn" data-bs-toggle="tab" data-bs-target="#tabNonSls" type="button" role="tab">
                            <i class="fas fa-list me-1"></i>Non-SLS (Prelist)
                            <span class="badge bg-secondary ms-1" id="nonslsBadgeCount"><?= number_format($total_prelist) ?></span>
                        </button>
                    </li>
                </ul>
                <button class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="loadSlsData(); loadNonSlsData();" title="Refresh"><i class="fas fa-sync-alt"></i></button>
            </div>
            <div class="card-body p-0 tab-content">
                <div class="tab-pane fade show active" id="tabSls" role="tabpanel">
                    <div class="p-2 border-bottom bg-light d-flex gap-2 align-items-center flex-wrap">
                        <input type="text" id="slsSearchInput" class="form-control form-control-sm" style="width:200px" placeholder="Cari SLS/kecamatan/desa..." onkeyup="slsSearchTimeout()">
                        <button class="btn btn-sm btn-se2026 py-0" onclick="loadSlsData()"><i class="fas fa-search"></i></button>
                    </div>
                    <div class="table-responsive" style="max-height:400px">
                        <table class="table table-sm table-hover mb-0" style="font-size:11px">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th>Wilayah</th>
                                    <th>Identitas SLS</th>
                                    <th class="text-center">KK</th>
                                    <th class="text-center">Usaha</th>
                                    <th class="text-center">Muatan</th>
                                    <th>Petugas</th>
                                    <th class="text-center">Status</th>
                                    <th>Assign Terakhir</th>
                                </tr>
                            </thead>
                            <tbody id="slsBody"></tbody>
                        </table>
                    </div>
                    <div class="p-2 small text-muted d-flex justify-content-between align-items-center">
                        <span id="slsInfo">0 baris</span>
                        <div>
                            <button class="btn btn-sm btn-outline-secondary py-0" id="slsPrevBtn" onclick="slsPageChange(-1)" disabled>&laquo; Sebelum</button>
                            <span id="slsPageInfo" class="mx-2">Halaman 1</span>
                            <button class="btn btn-sm btn-outline-secondary py-0" id="slsNextBtn" onclick="slsPageChange(1)" disabled>Berikut &raquo;</button>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="tabNonSls" role="tabpanel">
                    <div class="p-2 border-bottom bg-light d-flex gap-2 align-items-center flex-wrap">
                        <input type="text" id="nonslsSearchInput" class="form-control form-control-sm" style="width:200px" placeholder="Cari Non-SLS/kecamatan/desa..." onkeyup="nonslsSearchTimeout()">
                        <button class="btn btn-sm btn-info py-0 text-white" onclick="loadNonSlsData()"><i class="fas fa-search"></i></button>
                    </div>
                    <div class="table-responsive" style="max-height:400px">
                        <table class="table table-sm table-hover mb-0" style="font-size:11px">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th>Wilayah</th>
                                    <th>Identitas Non-SLS</th>
                                    <th class="text-center">KK</th>
                                    <th class="text-center">Usaha</th>
                                    <th class="text-center">Muatan</th>
                                    <th>Petugas</th>
                                    <th class="text-center">Status</th>
                                    <th>Assign Terakhir</th>
                                </tr>
                            </thead>
                            <tbody id="nonslsBody"></tbody>
                        </table>
                    </div>
                    <div class="p-2 small text-muted d-flex justify-content-between align-items-center">
                        <span id="nonslsInfo">0 baris</span>
                        <div>
                            <button class="btn btn-sm btn-outline-secondary py-0" id="nonslsPrevBtn" onclick="nonslsPageChange(-1)" disabled>&laquo; Sebelum</button>
                            <span id="nonslsPageInfo" class="mx-2">Halaman 1</span>
                            <button class="btn btn-sm btn-outline-secondary py-0" id="nonslsNextBtn" onclick="nonslsPageChange(1)" disabled>Berikut &raquo;</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-0">Kecamatan</label>
                <?php if (!empty($kecamatan_scope)): ?>
                    <?php
                    $scopeKecName = '';
                    foreach ($kecamatan as $kk) {
                        if (substr($kecamatan_scope, -3) === $kk['kdkec']) {
                            $scopeKecName = $kk['nmkec'];
                            break;
                        }
                    }
                    ?>
                    <input type="hidden" id="filterKdkec" value="<?= htmlspecialchars(substr($kecamatan_scope, -3)) ?>">
                    <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($scopeKecName) ?>" readonly disabled>
                <?php else: ?>
                <select id="filterKdkec" class="form-select form-select-sm" onchange="onFilterChange()">
                    <option value="">Semua Kecamatan</option>
                    <?php foreach ($kecamatan as $k): ?>
                        <option value="<?= htmlspecialchars($k['kdkec']) ?>"><?= htmlspecialchars($k['nmkec']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Desa</label>
                <select id="filterKddesa" class="form-select form-select-sm" onchange="onFilterChange()">
                    <option value="">Semua Desa</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Pencacah</label>
                <select id="filterPencacah" class="form-select form-select-sm" onchange="onFilterChange()">
                    <option value="">Semua PCL</option>
                    <?php foreach ($pencacah as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Pengawas</label>
                <select id="filterPengawas" class="form-select form-select-sm" onchange="onFilterChange()">
                    <option value="">Semua PML</option>
                    <?php foreach ($pengawas as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Task Force</label>
                <select id="filterTaskForce" class="form-select form-select-sm" onchange="onFilterChange()">
                    <option value="">Semua TF</option>
                    <?php foreach ($task_force as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <label class="form-label small mb-0">&nbsp;</label>
                <div class="d-flex gap-1 w-100">
                    <button class="btn btn-sm btn-outline-success flex-fill" onclick="exportExcel()">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="resetFilters()">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 small" id="tableMonitoring" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>Kecamatan</th>
                        <th>Desa</th>
                        <th>SLS</th>
                        <th class="text-center">KK</th>
                        <th class="text-center">Usaha</th>
                        <th class="text-center">Muatan</th>
                        <th>Pencacah</th>
                        <th>Pengawas</th>
                        <th>Task Force</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script>
var MONITORING_PER_PAGE = 10;
var slsPage = 0;
var nonslsPage = 0;
var slsSearchTimer = null;
var nonslsSearchTimer = null;
</script>

