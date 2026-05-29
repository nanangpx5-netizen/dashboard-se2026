<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="fas fa-monitor-waveform me-2"></i>Monitoring Wilayah</h5>
    <small class="text-muted"><?= date('d F Y H:i') ?> WIB</small>
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
                <h4 class="fw-bold mt-1 mb-0 text-success"><?= number_format($summary['assigned_sls'] ?? 0) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <small class="text-muted">Proses</small>
                <h4 class="fw-bold mt-1 mb-0 text-warning"><?= number_format($summary['progress_sls'] ?? 0) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <small class="text-muted">Selesai</small>
                <h4 class="fw-bold mt-1 mb-0 text-success"><?= number_format($summary['completed_sls'] ?? 0) ?></h4>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-0">Kecamatan</label>
                <select id="filterKdkec" class="form-select form-select-sm" onchange="onFilterChange()">
                    <option value="">Semua Kecamatan</option>
                    <?php foreach ($kecamatan as $k): ?>
                        <option value="<?= htmlspecialchars($k['kdkec']) ?>"><?= htmlspecialchars($k['nmkec']) ?></option>
                    <?php endforeach; ?>
                </select>
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

