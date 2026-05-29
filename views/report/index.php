<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="fas fa-file-alt me-2"></i>Laporan & Ekspor</h5>
    <small class="text-muted">Data siap pimpinan — Excel, PDF, Cetak</small>
</div>

<div class="row g-4">

    <!-- ─── REKAP PER KECAMATAN ────────────────────────────── -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                        <i class="fas fa-map-marked-alt fa-2x text-primary"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Rekap per Kecamatan</h6>
                        <small class="text-muted">Total SLS, muatan, progress, dan jumlah petugas per kecamatan</small>
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="?page=dashboard&sub=report&action=print&jenis=kecamatan" target="_blank" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-print me-1"></i>Cetak
                    </a>
                    <a href="?page=dashboard&sub=report&action=pdf&jenis=kecamatan" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </a>
                    <a href="?page=dashboard&sub=report&action=excel&jenis=kecamatan" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </a>
                    <a href="?page=dashboard&sub=report&action=csv&jenis=kecamatan" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-file-csv me-1"></i>CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── REKAP PER PENCACAH ─────────────────────────────── -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                        <i class="fas fa-user-check fa-2x text-success"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Rekap per Pencacah (PCL)</h6>
                        <small class="text-muted">Beban kerja, status penyelesaian, dan wilayah tugas per PCL</small>
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="?page=dashboard&sub=report&action=print&jenis=pencacah" target="_blank" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-print me-1"></i>Cetak
                    </a>
                    <a href="?page=dashboard&sub=report&action=pdf&jenis=pencacah" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </a>
                    <a href="?page=dashboard&sub=report&action=excel&jenis=pencacah" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </a>
                    <a href="?page=dashboard&sub=report&action=csv&jenis=pencacah" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-file-csv me-1"></i>CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── REKAP PER PENGAWAS ─────────────────────────────── -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                        <i class="fas fa-user-shield fa-2x text-warning"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Rekap per Pengawas (PML)</h6>
                        <small class="text-muted">Beban kerja, status penyelesaian, dan wilayah tugas per PML</small>
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="?page=dashboard&sub=report&action=print&jenis=pengawas" target="_blank" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-print me-1"></i>Cetak
                    </a>
                    <a href="?page=dashboard&sub=report&action=pdf&jenis=pengawas" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </a>
                    <a href="?page=dashboard&sub=report&action=excel&jenis=pengawas" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </a>
                    <a href="?page=dashboard&sub=report&action=csv&jenis=pengawas" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-file-csv me-1"></i>CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── DASHBOARD SNAPSHOT ─────────────────────────────── -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-info bg-opacity-10 p-3 rounded">
                        <i class="fas fa-camera fa-2x text-info"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Dashboard Snapshot</h6>
                        <small class="text-muted">Ringkasan eksekutif + rekap per kecamatan dalam satu laporan</small>
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="?page=dashboard&sub=report&action=print&jenis=snapshot" target="_blank" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-print me-1"></i>Cetak
                    </a>
                    <a href="?page=dashboard&sub=report&action=pdf&jenis=snapshot" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </a>
                    <a href="?page=dashboard&sub=report&action=excel&jenis=snapshot" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </a>
                    <a href="?page=dashboard&sub=report&action=csv&jenis=snapshot" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-file-csv me-1"></i>CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── DETAIL WILAYAH (filtered) ──────────────────────── -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-secondary bg-opacity-10 p-3 rounded">
                        <i class="fas fa-list-alt fa-2x text-secondary"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Detail Wilayah</h6>
                        <small class="text-muted">Data per SLS dengan filter kecamatan</small>
                    </div>
                </div>

                <form class="row g-2 align-items-end" method="GET" action="?page=dashboard&sub=report" target="_blank">
                    <input type="hidden" name="page" value="dashboard">
                    <input type="hidden" name="sub" value="report">
                    <input type="hidden" name="jenis" value="detail">
                    <div class="col-md-3">
                        <label class="form-label small">Filter Kecamatan</label>
                        <select name="kdkec" class="form-select form-select-sm">
                            <option value="">Semua Kecamatan</option>
                            <?php foreach ($kecamatan as $k): ?>
                                <option value="<?= htmlspecialchars($k['kdkec']) ?>"><?= htmlspecialchars($k['nmkec']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-9 d-flex gap-2">
                        <button type="submit" name="action" value="print" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-print me-1"></i>Cetak
                        </button>
                        <button type="submit" name="action" value="pdf" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-file-pdf me-1"></i>PDF
                        </button>
                        <button type="submit" name="action" value="excel" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-file-excel me-1"></i>Excel
                        </button>
                        <button type="submit" name="action" value="csv" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-file-csv me-1"></i>CSV
                        </button>
                    </div>
                </form>
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
                    Data diambil secara <strong>real-time</strong> dari database.
                    Format laporan siap cetak dan distribusi ke pimpinan.
                </small>
            </div>
            <div class="col-md-4 text-md-end">
                <small class="text-muted">
                    <i class="fas fa-sync-alt me-1"></i>
                    Terakhir: <?= date('d/m/Y H:i') ?> WIB
                </small>
            </div>
        </div>
    </div>
</div>
