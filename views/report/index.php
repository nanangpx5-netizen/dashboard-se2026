<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="fas fa-file-alt me-2"></i>Laporan & Ekspor</h5>
    <small class="text-muted" id="reportClock"><?= date('d F Y H:i') ?> WIB</small>
</div>

<div class="row g-2 mb-4">
    <div class="col-md-3 col-6">
        <div class="card card-jember h-100 border-0 shadow-sm">
            <div class="card-body py-2 text-center">
                <small class="text-muted d-block">Total SLS (SIPW)</small>
                <span class="fw-bold fs-4 text-jember" id="stTotalSls"><?= number_format($exec['total_sls'] ?? 0) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card card-jember h-100 border-0 shadow-sm">
            <div class="card-body py-2 text-center">
                <small class="text-muted d-block">SLS Assigned</small>
                <span class="fw-bold fs-4" id="stAssigned"><?= number_format($exec['assigned'] ?? 0) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card card-jember h-100 border-0 shadow-sm">
            <div class="card-body py-2 text-center">
                <small class="text-muted d-block">Selesai</small>
                <span class="fw-bold fs-4 text-success" id="stSelesai"><?= number_format($exec['selesai'] ?? 0) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card card-jember h-100 border-0 shadow-sm">
            <div class="card-body py-2 text-center">
                <small class="text-muted d-block">Progress</small>
                <span class="fw-bold fs-4" id="stProgress"><?= number_format($exec['progress'] ?? 0, 1) ?>%</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-se2026-light p-3 rounded">
                        <i class="fas fa-map-marked-alt fa-2x text-se2026"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Rekap per Kecamatan</h6>
                        <small class="text-muted">Total SLS, muatan, progress, PCL/PML per kecamatan</small>
                    </div>
                </div>
                <div class="mb-2" id="previewKecamatan" style="max-height:180px; overflow-y:auto;">
                    <div class="text-center text-muted small py-2"><span class="spinner-border spinner-border-sm me-1"></span>Memuat...</div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="?page=dashboard&sub=report&action=preview&jenis=kecamatan" class="btn btn-sm btn-outline-primary preview-btn" data-preview="kecamatan">
                        <i class="fas fa-eye me-1"></i>Preview
                    </a>
                    <a href="?page=dashboard&sub=report&action=excel&jenis=kecamatan" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </a>
                    <a href="?page=dashboard&sub=report&action=csv&jenis=kecamatan" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-file-csv me-1"></i>CSV
                    </a>
                    <a href="?page=dashboard&sub=report&action=pdf&jenis=kecamatan" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </a>
                    <a href="?page=dashboard&sub=report&action=print&jenis=kecamatan" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-print me-1"></i>Cetak
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                        <i class="fas fa-user-check fa-2x text-success"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Rekap per Pencacah (PCL)</h6>
                        <small class="text-muted">Beban kerja & status per PCL</small>
                    </div>
                </div>
                <div class="mb-2" id="previewPencacah" style="max-height:180px; overflow-y:auto;">
                    <div class="text-center text-muted small py-2"><span class="spinner-border spinner-border-sm me-1"></span>Memuat...</div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="?page=dashboard&sub=report&action=preview&jenis=pencacah" class="btn btn-sm btn-outline-primary preview-btn" data-preview="pencacah">
                        <i class="fas fa-eye me-1"></i>Preview
                    </a>
                    <a href="?page=dashboard&sub=report&action=excel&jenis=pencacah" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </a>
                    <a href="?page=dashboard&sub=report&action=csv&jenis=pencacah" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-file-csv me-1"></i>CSV
                    </a>
                    <a href="?page=dashboard&sub=report&action=pdf&jenis=pencacah" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </a>
                    <a href="?page=dashboard&sub=report&action=print&jenis=pencacah" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-print me-1"></i>Cetak
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                        <i class="fas fa-user-shield fa-2x text-warning"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Rekap per Pengawas (PML)</h6>
                        <small class="text-muted">Beban kerja & status per PML</small>
                    </div>
                </div>
                <div class="mb-2" id="previewPengawas" style="max-height:180px; overflow-y:auto;">
                    <div class="text-center text-muted small py-2"><span class="spinner-border spinner-border-sm me-1"></span>Memuat...</div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="?page=dashboard&sub=report&action=preview&jenis=pengawas" class="btn btn-sm btn-outline-primary preview-btn" data-preview="pengawas">
                        <i class="fas fa-eye me-1"></i>Preview
                    </a>
                    <a href="?page=dashboard&sub=report&action=excel&jenis=pengawas" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </a>
                    <a href="?page=dashboard&sub=report&action=csv&jenis=pengawas" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-file-csv me-1"></i>CSV
                    </a>
                    <a href="?page=dashboard&sub=report&action=pdf&jenis=pengawas" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </a>
                    <a href="?page=dashboard&sub=report&action=print&jenis=pengawas" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-print me-1"></i>Cetak
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-info bg-opacity-10 p-3 rounded">
                        <i class="fas fa-people-carry-box fa-2x text-info"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Rekap per Task Force</h6>
                        <small class="text-muted">Beban kerja & status per TF</small>
                    </div>
                </div>
                <div class="mb-2" id="previewTaskForce" style="max-height:180px; overflow-y:auto;">
                    <div class="text-center text-muted small py-2"><span class="spinner-border spinner-border-sm me-1"></span>Memuat...</div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="?page=dashboard&sub=report&action=preview&jenis=task_force" class="btn btn-sm btn-outline-primary preview-btn" data-preview="task_force">
                        <i class="fas fa-eye me-1"></i>Preview
                    </a>
                    <a href="?page=dashboard&sub=report&action=excel&jenis=task_force" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </a>
                    <a href="?page=dashboard&sub=report&action=csv&jenis=task_force" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-file-csv me-1"></i>CSV
                    </a>
                    <a href="?page=dashboard&sub=report&action=pdf&jenis=task_force" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </a>
                    <a href="?page=dashboard&sub=report&action=print&jenis=task_force" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-print me-1"></i>Cetak
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-secondary bg-opacity-10 p-3 rounded">
                        <i class="fas fa-camera fa-2x text-secondary"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Dashboard Snapshot</h6>
                        <small class="text-muted">Ringkasan eksekutif + rekap per kecamatan</small>
                    </div>
                </div>
                <div class="mb-2" id="previewSnapshot" style="max-height:180px; overflow-y:auto;">
                    <div class="text-center text-muted small py-2"><span class="spinner-border spinner-border-sm me-1"></span>Memuat...</div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="?page=dashboard&sub=report&action=excel&jenis=snapshot" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </a>
                    <a href="?page=dashboard&sub=report&action=pdf&jenis=snapshot" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </a>
                    <a href="?page=dashboard&sub=report&action=print&jenis=snapshot" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-print me-1"></i>Cetak
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 card-jatim">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-se2026-light p-3 rounded">
                        <i class="fas fa-clipboard-list fa-2x text-se2026"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Prelist SE2026 <span class="badge badge-jatim" style="font-size:8px">Jember</span></h6>
                        <small class="text-muted">Data prelist per kecamatan hasil SE2026</small>
                    </div>
                </div>
                <div class="mb-2" id="previewPrelist" style="max-height:180px; overflow-y:auto;">
                    <div class="text-center text-muted small py-2"><span class="spinner-border spinner-border-sm me-1"></span>Memuat...</div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="?page=dashboard&sub=report&action=excel&jenis=prelist" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </a>
                    <a href="?page=dashboard&sub=report&action=csv&jenis=prelist" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-file-csv me-1"></i>CSV
                    </a>
                    <a href="?page=dashboard&sub=report&action=pdf&jenis=prelist" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </a>
                    <a href="?page=dashboard&sub=report&action=print&jenis=prelist" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-print me-1"></i>Cetak
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm card-jember">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-jember-light p-3 rounded">
                        <i class="fas fa-list-alt fa-2x text-jember"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Detail Wilayah</h6>
                        <small class="text-muted">Data per SLS dengan filter</small>
                    </div>
                </div>
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label small">Kecamatan</label>
                        <select id="detKdkec" class="form-select form-select-sm">
                            <option value="">Semua Kecamatan</option>
                            <?php foreach ($kecamatan as $k): ?>
                                <option value="<?= htmlspecialchars($k['kdkec']) ?>"><?= htmlspecialchars($k['nmkec']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8 d-flex gap-2">
                        <button class="btn btn-sm btn-se2026" onclick="loadDetailPreview()"><i class="fas fa-search me-1"></i>Preview</button>
                        <a href="#" id="detExcelLink" class="btn btn-sm btn-outline-success"><i class="fas fa-file-excel me-1"></i>Excel</a>
                        <a href="#" id="detCsvLink" class="btn btn-sm btn-outline-info"><i class="fas fa-file-csv me-1"></i>CSV</a>
                        <a href="#" id="detPdfLink" class="btn btn-sm btn-outline-danger"><i class="fas fa-file-pdf me-1"></i>PDF</a>
                        <a href="#" id="detPrintLink" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print me-1"></i>Cetak</a>
                    </div>
                </div>
                <div id="previewDetail" style="max-height:240px; overflow-y:auto;">
                    <div class="text-center text-muted small py-4">Pilih kecamatan & klik Preview</div>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body py-2 px-3">
        <div class="row align-items-center">
            <div class="col-md-8">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Data diambil secara <strong>real-time</strong> dari database. Laporan siap cetak & distribusi.
                </small>
            </div>
            <div class="col-md-4 text-md-end">
                <small class="text-muted">
                    <i class="fas fa-clock me-1"></i>
                    Terakhir: <span id="footerClock"><?= date('d/m/Y H:i') ?> WIB</span>
                </small>
            </div>
        </div>
    </div>
</div>

<script>
function esc(str) { if (!str) return '-'; var d = document.createElement('div'); d.appendChild(document.createTextNode(str)); return d.innerHTML; }

function loadAllPreviews() {
    var types = ['kecamatan', 'pencacah', 'pengawas', 'task_force'];
    types.forEach(function(t) { loadPreview(t, ''); });
    loadSnapshotPreview();
    loadPrelistPreview();
}

function loadPreview(jenis, kdkec) {
    var el = document.getElementById('preview' + jenis.charAt(0).toUpperCase() + jenis.slice(1));
    if (!el) return;
    var url = '?page=dashboard&sub=report&action=preview&jenis=' + jenis;
    if (kdkec) url += '&kdkec=' + encodeURIComponent(kdkec);
    fetch(url).then(function(r) { return r.json(); }).then(function(res) {
        if (!res.success || !res.data || !res.data.rows) {
            el.innerHTML = '<div class="text-center text-muted small py-2">Tidak ada data</div>'; return;
        }
        var rows = res.data.rows;
        if (jenis === 'kecamatan') renderKecPreview(el, rows);
        else if (jenis === 'pencacah' || jenis === 'pengawas' || jenis === 'task_force') renderPetugasPreview(el, rows, jenis);
    }).catch(function() {
        el.innerHTML = '<div class="text-center text-danger small py-2">Gagal memuat</div>';
    });
}

function renderKecPreview(el, rows) {
    if (!rows.length) { el.innerHTML = '<div class="text-center text-muted small py-2">Tidak ada data</div>'; return; }
    var h = '<table class="table table-sm table-hover mb-0 small"><thead class="table-light"><tr><th>#</th><th>Kecamatan</th><th class="text-center">SLS</th><th class="text-center">Assign</th><th class="text-center">Proses</th><th class="text-center">Selesai</th><th class="text-center">PCL</th><th class="text-center">PML</th></tr></thead><tbody>';
    rows.forEach(function(r, i) {
        h += '<tr><td>' + (i+1) + '</td><td>' + esc(r.kecamatan) + '</td><td class="text-center">' + Number(r.total_sls).toLocaleString() + '</td><td class="text-center">' + Number(r.assigned).toLocaleString() + '</td><td class="text-center">' + Number(r.proses).toLocaleString() + '</td><td class="text-center">' + Number(r.selesai).toLocaleString() + '</td><td class="text-center">' + Number(r.jumlah_pcl).toLocaleString() + '</td><td class="text-center">' + Number(r.jumlah_pml).toLocaleString() + '</td></tr>';
    });
    h += '</tbody></table>';
    el.innerHTML = h;
}

function renderPetugasPreview(el, rows, jenis) {
    if (!rows.length) { el.innerHTML = '<div class="text-center text-muted small py-2">Belum ada assignment</div>'; return; }
    var label = jenis === 'task_force' ? 'TF' : (jenis === 'pengawas' ? 'PML' : 'PCL');
    var h = '<table class="table table-sm table-hover mb-0 small"><thead class="table-light"><tr><th>#</th><th>Nama ' + label + '</th><th class="text-center">SLS</th><th class="text-center">Selesai</th><th class="text-center">Proses</th><th class="text-center">Belum</th><th>Wilayah</th></tr></thead><tbody>';
    rows.forEach(function(r, i) {
        h += '<tr><td>' + (i+1) + '</td><td>' + esc(r.username) + '</td><td class="text-center">' + Number(r.total_sls).toLocaleString() + '</td><td class="text-center text-success">' + Number(r.selesai).toLocaleString() + '</td><td class="text-center text-warning">' + Number(r.proses).toLocaleString() + '</td><td class="text-center text-muted">' + Number(r.belum).toLocaleString() + '</td><td style="font-size:10px">' + esc(r.kecamatan) + '</td></tr>';
    });
    h += '</tbody></table>';
    el.innerHTML = h;
}

function loadSnapshotPreview() {
    fetch('?page=dashboard&sub=report&action=preview&jenis=snapshot').then(function(r) { return r.json(); }).then(function(res) {
        var el = document.getElementById('previewSnapshot');
        if (!res.success || !res.data) { el.innerHTML = '<div class="text-center text-muted small py-2">Tidak ada data</div>'; return; }
        var s = res.data.summary || {};
        var e = res.data.exec || {};
        var pct = (e.total_sls && e.total_sls > 0) ? (e.selesai / e.total_sls * 100).toFixed(1) + '%' : '0%';
        el.innerHTML = '<div class="row g-1 small"><div class="col-4"><span class="text-muted">Kecamatan</span> <span class="fw-semibold">' + Number(s.total_kecamatan).toLocaleString() + '</span></div><div class="col-4"><span class="text-muted">Desa</span> <span class="fw-semibold">' + Number(s.total_desa).toLocaleString() + '</span></div><div class="col-4"><span class="text-muted">SLS</span> <span class="fw-semibold">' + Number(s.total_sls).toLocaleString() + '</span></div><div class="col-4"><span class="text-muted">KK</span> <span class="fw-semibold">' + Number(s.total_kk).toLocaleString() + '</span></div><div class="col-4"><span class="text-muted">Muatan</span> <span class="fw-semibold">' + Number(s.total_muatan).toLocaleString() + '</span></div><div class="col-4"><span class="text-muted">Progress</span> <span class="fw-semibold">' + pct + '</span></div><div class="col-4"><span class="text-muted">PCL</span> <span class="fw-semibold">' + Number(s.total_pcl).toLocaleString() + '</span></div><div class="col-4"><span class="text-muted">PML</span> <span class="fw-semibold">' + Number(s.total_pml).toLocaleString() + '</span></div><div class="col-4"><span class="text-muted">Assigned</span> <span class="fw-semibold">' + Number(s.assigned).toLocaleString() + '</span></div></div>';
    }).catch(function() {});
}

function loadPrelistPreview() {
    fetch('?page=dashboard&sub=report&action=preview&jenis=prelist').then(function(r) { return r.json(); }).then(function(res) {
        var el = document.getElementById('previewPrelist');
        if (!res.success || !res.data) { el.innerHTML = '<div class="text-center text-muted small py-2">Tidak ada data</div>'; return; }
        var s = res.data.summary || {};
        var rows = res.data.per_kec || [];
        var h = '<div class="row g-1 small mb-2"><div class="col-4"><span class="text-muted">Kecamatan</span> <span class="fw-semibold">' + Number(s.total_kecamatan).toLocaleString() + '</span></div><div class="col-4"><span class="text-muted">Desa</span> <span class="fw-semibold">' + Number(s.total_desa).toLocaleString() + '</span></div><div class="col-4"><span class="text-muted">SLS</span> <span class="fw-semibold">' + Number(s.total_sls).toLocaleString() + '</span></div><div class="col-4"><span class="text-muted">KK</span> <span class="fw-semibold">' + Number(s.total_kk).toLocaleString() + '</span></div><div class="col-4"><span class="text-muted">UTP</span> <span class="fw-semibold">' + Number(s.total_utp).toLocaleString() + '</span></div><div class="col-4"><span class="text-muted">Muatan</span> <span class="fw-semibold">' + Number(s.total_muatan).toLocaleString() + '</span></div></div>';
        if (rows.length) {
            h += '<table class="table table-sm table-hover mb-0 small"><thead class="table-light"><tr><th>#</th><th>Kecamatan</th><th class="text-center">Desa</th><th class="text-center">SLS</th><th class="text-center">KK</th><th class="text-center">Muatan</th></tr></thead><tbody>';
            rows.forEach(function(r, i) {
                h += '<tr><td>' + (i+1) + '</td><td>' + esc(r.kecamatan) + '</td><td class="text-center">' + Number(r.total_desa).toLocaleString() + '</td><td class="text-center">' + Number(r.total_sls).toLocaleString() + '</td><td class="text-center">' + Number(r.total_kk).toLocaleString() + '</td><td class="text-center">' + Number(r.total_muatan).toLocaleString() + '</td></tr>';
            });
            h += '</tbody></table>';
        }
        el.innerHTML = h;
    }).catch(function() {});
}

function loadDetailPreview() {
    var kdkec = document.getElementById('detKdkec').value;
    var el = document.getElementById('previewDetail');
    el.innerHTML = '<div class="text-center text-muted small py-2"><span class="spinner-border spinner-border-sm me-1"></span>Memuat...</div>';

    var url = '?page=dashboard&sub=report&action=preview&jenis=detail';
    if (kdkec) url += '&kdkec=' + encodeURIComponent(kdkec);

    // Update export links
    var base = '?page=dashboard&sub=report';
    document.getElementById('detExcelLink').href = base + '&action=excel&jenis=detail' + (kdkec ? '&kdkec=' + kdkec : '');
    document.getElementById('detCsvLink').href = base + '&action=csv&jenis=detail' + (kdkec ? '&kdkec=' + kdkec : '');
    document.getElementById('detPdfLink').href = base + '&action=pdf&jenis=detail' + (kdkec ? '&kdkec=' + kdkec : '');
    document.getElementById('detPrintLink').href = base + '&action=print&jenis=detail' + (kdkec ? '&kdkec=' + kdkec : '');

    fetch(url).then(function(r) { return r.json(); }).then(function(res) {
        if (!res.success || !res.data || !res.data.rows) {
            el.innerHTML = '<div class="text-center text-muted small py-4">Tidak ada data</div>'; return;
        }
        var rows = res.data.rows;
        var h = '<table class="table table-sm table-hover mb-0 small"><thead class="table-light"><tr><th>#</th><th>Kecamatan</th><th>Desa</th><th>SLS</th><th class="text-center">KK</th><th class="text-center">Muatan</th><th>PCL</th><th>PML</th><th class="text-center">Status</th></tr></thead><tbody>';
        rows.forEach(function(r, i) {
            var st = r.status || 'belum';
            var badge = st === 'selesai' ? 'bg-success' : (st === 'proses' ? 'bg-warning text-dark' : 'bg-secondary');
            h += '<tr><td>' + (i+1) + '</td><td>' + esc(r.kecamatan) + '</td><td>' + esc(r.desa) + '</td><td>' + esc(r.sls) + '</td><td class="text-center">' + Number(r.kk).toLocaleString() + '</td><td class="text-center">' + Number(r.muatan).toLocaleString() + '</td><td>' + esc(r.pencacah) + '</td><td>' + esc(r.pengawas) + '</td><td class="text-center"><span class="badge ' + badge + '" style="font-size:9px">' + st + '</span></td></tr>';
        });
        h += '</tbody></table>';
        el.innerHTML = h;
    }).catch(function() {
        el.innerHTML = '<div class="text-center text-danger small py-4">Gagal memuat</div>';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    loadAllPreviews();
});
</script>