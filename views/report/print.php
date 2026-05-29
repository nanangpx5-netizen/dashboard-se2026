<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="fas fa-print me-2"></i>Cetak Laporan</h5>
    <button class="btn btn-primary btn-sm" onclick="window.print()">
        <i class="fas fa-print me-1"></i>Cetak / PDF
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?= $html ?>
    </div>
</div>

<style>
@media print {
    .sidebar, .navbar, footer, .btn, .card-header, .card-body > .d-flex { display: none !important; }
    .card { box-shadow: none !important; border: none !important; }
    body { background: white; }
}
</style>
