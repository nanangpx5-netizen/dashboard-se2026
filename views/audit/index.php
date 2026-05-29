<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="fas fa-history me-2"></i>Audit Log</h5>
    <small class="text-muted">Riwayat aktivitas sistem</small>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-0">Modul</label>
                <select id="filterModule" class="form-select form-select-sm" onchange="reloadTable()">
                    <option value="">Semua Modul</option>
                    <?php foreach ($modules as $m): ?>
                        <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars(ucfirst($m)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">User</label>
                <select id="filterUser" class="form-select form-select-sm" onchange="reloadTable()">
                    <option value="">Semua User</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Dari Tanggal</label>
                <input type="date" id="filterDateFrom" class="form-control form-control-sm" onchange="reloadTable()">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Sampai Tanggal</label>
                <input type="date" id="filterDateTo" class="form-control form-control-sm" onchange="reloadTable()">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <label class="form-label small mb-0">&nbsp;</label>
                <div class="d-flex gap-1 w-100">
                    <button class="btn btn-sm btn-outline-secondary" onclick="resetFilters()">
                        <i class="fas fa-undo me-1"></i>Reset
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 small" id="tableAuditLog" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>Waktu</th>
                        <th>User</th>
                        <th>Aksi</th>
                        <th>Modul</th>
                        <th>Keterangan</th>
                        <th class="text-center">Detail</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

