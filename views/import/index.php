<?php
/**
 * Helper: render pagination links untuk Riwayat Import
 */
function renderImportPagination(int $page, int $totalPages, int $perPage): void
{
    if ($totalPages <= 1) return;
    $qs = $_GET;
    unset($qs['hal']);
    $base = '?' . http_build_query(array_merge($qs, ['per_page' => $perPage]));
?>
<nav>
    <ul class="pagination pagination-sm justify-content-center my-2">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $base ?>&hal=1">&laquo;</a>
        </li>
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $base ?>&hal=<?= max(1, $page - 1) ?>">&lsaquo;</a>
        </li>
        <?php
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        if ($start > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        for ($i = $start; $i <= $end; $i++):
        ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= $base ?>&hal=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor;
        if ($end < $totalPages) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $base ?>&hal=<?= min($totalPages, $page + 1) ?>">&rsaquo;</a>
        </li>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $base ?>&hal=<?= $totalPages ?>">&raquo;</a>
        </li>
    </ul>
</nav>
<?php } ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="fas fa-file-import me-2"></i>Import SIPW</h5>
    <span>
        <small class="text-muted me-2">Data existing:</small>
        <span class="badge badge-se2026"><?= number_format($total_sls) ?> SLS</span>
        <span class="badge bg-info"><?= number_format($total_kec) ?> Kec</span>
        <span class="badge bg-secondary"><?= number_format($total_desa) ?> Desa</span>
        <span class="badge bg-success"><?= number_format($total_muatan) ?> Muatan</span>
    </span>
</div>

<?php if (!empty($flash)): ?>
    <?php foreach ($flash as $type => $message): ?>
        <?php if ($type === 'batch_id') continue; ?>
        <div class="alert alert-<?= $type === 'error' ? 'danger' : ($type === 'success' ? 'success' : ($type === 'warning' ? 'warning' : 'info')) ?> alert-dismissible fade show py-2 small" role="alert">
            <?php if ($type !== 'info' && $type !== 'warning'): ?>
                <i class="fas fa-<?= $type === 'error' ? 'exclamation-circle' : 'check-circle' ?> me-1"></i>
            <?php endif; ?>
            <?= e($flash[$type]) ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($stats)): ?>
<div class="row g-2 mb-3">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100 text-center py-2">
            <small class="text-muted">Total Import</small>
            <span class="fw-bold fs-5"><?= number_format($stats['total_import']) ?></span>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100 text-center py-2">
            <small class="text-muted">Berhasil</small>
            <span class="fw-bold fs-5 text-success"><?= number_format($stats['total_berhasil']) ?></span>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100 text-center py-2">
            <small class="text-muted">Diupdate</small>
            <span class="fw-bold fs-5 text-warning"><?= number_format($stats['total_diupdate']) ?></span>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100 text-center py-2">
            <small class="text-muted">Gagal</small>
            <span class="fw-bold fs-5 text-danger"><?= number_format($stats['total_gagal']) ?></span>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($preview_info) && $has_file): ?>
    <!-- ─── PREVIEW SECTION ──────────────────────────────────────────── -->
    <?php $pv = $preview_info; ?>
    <div class="card border-0 shadow-sm mb-4 border-start border-warning border-4">
        <div class="card-header bg-warning bg-opacity-10 d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold small">
                <i class="fas fa-eye me-1"></i>Preview Data
            </span>
            <div>
                <span class="badge bg-light text-dark me-2">
                    <i class="fas fa-file me-1"></i><?= htmlspecialchars($pv['nama_file']) ?>
                </span>
                <span class="badge badge-se2026"><?= number_format($pv['total_baris']) ?> baris</span>
                <span class="badge bg-secondary"><?= number_format($pv['ukuran_file'] / 1024, 1) ?> KB</span>
            </div>
        </div>
        <div class="card-body p-3">
            <!-- Header validation -->
            <?php if (!empty($pv['missing_recommended'])): ?>
                <div class="alert alert-warning py-1 small mb-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Kolom rekomendasi tidak ditemukan: <strong><?= implode(', ', $pv['missing_recommended']) ?></strong>.
                    Data tetap dapat di-import.
                </div>
            <?php endif; ?>

            <?php if (!empty($pv['unmapped_headers'])): ?>
                <div class="alert alert-info py-1 small mb-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Kolom yang tidak dikenali: <?= implode(', ', array_column($pv['unmapped_headers'], 'header')) ?>.
                    Kolom ini akan diabaikan.
                </div>
            <?php endif; ?>

            <!-- Column mapping -->
            <div class="small mb-2">
                <span class="fw-semibold">Mapping kolom terdeteksi:</span>
                <?php if (!empty($pv['mapping'])): ?>
                    <?php $mappedCount = count(array_filter($pv['mapping'], fn($v) => $v !== null)); ?>
                    <span class="badge bg-success"><?= $mappedCount ?>/<?= count($pv['mapping']) ?> kolom terpetakan</span>
                <?php endif; ?>
            </div>

            <!-- Sample table -->
            <div class="table-responsive" style="max-height: 450px;">
                <table class="table table-sm table-bordered table-striped small mb-0" id="previewTable">
                    <thead class="table-dark" style="position: sticky; top: 0; z-index: 1;">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Kec</th>
                            <th>Desa</th>
                            <th>SLS</th>
                            <th>Nama SLS</th>
                            <th>Ketua</th>
                            <th class="text-end">KK</th>
                            <th class="text-end">BTT</th>
                            <th class="text-end">BKU</th>
                            <th class="text-end">Usaha</th>
                            <th class="text-end">Muatan</th>
                            <th style="width:60px">Status</th>
                        </tr>
                    </thead>
                    <tbody id="previewBody">
                        <?php foreach ($preview_info_full ?? $preview_info['sample'] ?? [] as $i => $sample): ?>
                            <?php $r = $sample['row'] ?? $sample; ?>
                            <?php $valid = $sample['valid'] ?? true; ?>
                            <tr class="<?= $valid ? '' : 'table-danger' ?>">
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($r['nmkec'] ?? $r['kdkec'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($r['nmdesa'] ?? $r['kddesa'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($r['kdsls'] ?? $r['idsubsls'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($r['nmsls'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($r['nama_ketua'] ?? '-') ?></td>
                                <td class="text-end"><?= number_format($r['kk'] ?? 0) ?></td>
                                <td class="text-end"><?= number_format($r['btt'] ?? 0) ?></td>
                                <td class="text-end"><?= number_format($r['bku'] ?? 0) ?></td>
                                <td class="text-end"><?= number_format($r['usaha'] ?? 0) ?></td>
                                <td class="text-end fw-semibold"><?= number_format($r['muatan'] ?? 0) ?></td>
                                <td>
                                    <?php if ($valid): ?>
                                        <i class="fas fa-check-circle text-success" title="Valid"></i>
                                    <?php else: ?>
                                        <i class="fas fa-exclamation-circle text-danger" title="<?= htmlspecialchars(implode('; ', $sample['errors'] ?? [])) ?>"></i>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Menampilkan <?= number_format(min(50, $pv['total_baris'])) ?> dari <?= number_format($pv['total_baris']) ?> baris.
                    <?php if ($pv['total_baris'] > 50): ?>
                        <a href="#" onclick="loadPreviewPage(2); return false;" class="text-se2026">Muat lebih banyak</a>
                    <?php endif; ?>
                </small>
                <div class="d-flex gap-2">
                    <form method="POST" action="?page=dashboard&sub=import&action=import" onsubmit="return confirm('Import <?= number_format($pv['total_baris']) ?> baris data?\nBaris baru akan ditambahkan, duplikat akan diupdate.')">
                        <?= $csrf_field ?? '' ?>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fas fa-database me-1"></i>Proses Import
                        </button>
                    </form>
                    <form method="POST" action="?page=dashboard&sub=import&action=cancel">
                        <?= $csrf_field ?? '' ?>
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-1"></i>Batal
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ─── UPLOAD FORM ───────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="POST" action="?page=dashboard&sub=import&action=upload" enctype="multipart/form-data">
                <?= $csrf_field ?? '' ?>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold small">Pilih File Excel SIPW</label>
                        <input type="file" name="import_file" class="form-control"
                               accept=".xlsx,.xls,.csv" required>
                        <div class="form-text small">
                            Format: <strong>XLSX</strong>, XLS, atau CSV.
                            Maksimal <strong>20 MB</strong>.
                            File diekstrak secara streaming — aman untuk &gt;5000 baris.
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-se2026 w-100">
                            <i class="fas fa-upload me-1"></i>Upload &amp; Preview
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Format info -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <h6 class="fw-semibold small mb-2"><i class="fas fa-list-check me-1"></i>Format Kolom yang Didukung</h6>
            <div class="row small">
                <div class="col-md-4">
                    <span class="fw-semibold">Wajib:</span>
                    <code>kode_kecamatan</code>, <code>kode_desa</code>
                </div>
                <div class="col-md-4">
                    <span class="fw-semibold">Rekomendasi:</span>
                    <code>nama_sls</code>, <code>total_muatan</code>
                </div>
                <div class="col-md-4">
                    <span class="fw-semibold">Lainnya:</span>
                    <code>id_frs</code>, <code>semester</code>, <code>nama_ketua</code>, <code>kk</code>, <code>btt</code>, <code>bku</code>, <code>usaha</code>
                </div>
            </div>
            <div class="small text-muted mt-1">
                <i class="fas fa-lightbulb me-1"></i>
                Nama kolom dikenali secara fleksibel (contoh: <code>kdkec</code>, <code>kode_kec</code>, <code>kode_kecamatan</code>, <code>kec</code>).
                Baris duplikat (berdasarkan <code>id_frs</code> atau kombinasi kode wilayah) akan di-<em>update</em>, bukan ditolak.
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ─── IMPORT HISTORY ──────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
        <span class="fw-semibold small"><i class="fas fa-history me-1"></i>Riwayat Import</span>
        <div class="d-flex align-items-center gap-2">
            <small class="text-muted">
                <?= number_format($history_total ?? 0) ?> total
                <?php if (($total_pages ?? 1) > 1): ?>
                    &middot; hal <?= $page_num ?? 1 ?> / <?= $total_pages ?? 1 ?>
                <?php endif; ?>
            </small>
            <form method="GET" action="?page=dashboard&sub=import" class="d-inline-flex align-items-center gap-1">
                <input type="hidden" name="page" value="dashboard">
                <input type="hidden" name="sub" value="import">
                <select name="per_page" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    <option value="10"  <?= ($per_page ?? 10) === 10  ? 'selected' : '' ?>>10</option>
                    <option value="25"  <?= ($per_page ?? 10) === 25  ? 'selected' : '' ?>>25</option>
                    <option value="50"  <?= ($per_page ?? 10) === 50  ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= ($per_page ?? 10) === 100 ? 'selected' : '' ?>>100</option>
                </select>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Batch ID</th>
                        <th>File</th>
                        <th class="text-center">Baris</th>
                        <th class="text-center">Baru</th>
                        <th class="text-center">Update</th>
                        <th class="text-center">Gagal</th>
                        <th>Status</th>
                        <th>User</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">Belum ada riwayat import.</td></tr>
                    <?php else: ?>
                        <?php foreach ($history as $h): ?>
                        <tr>
                            <td><code class="small"><?= htmlspecialchars($h['batch_id']) ?></code></td>
                            <td><small><?= htmlspecialchars($h['nama_file']) ?></small></td>
                            <td class="text-center"><?= number_format($h['total_baris']) ?></td>
                            <td class="text-center text-success fw-semibold"><?= number_format($h['baris_berhasil']) ?></td>
                            <td class="text-center text-warning fw-semibold"><?= number_format($h['baris_diupdate']) ?></td>
                            <td class="text-center <?= $h['baris_gagal'] > 0 ? 'text-danger fw-semibold' : '' ?>"><?= number_format($h['baris_gagal']) ?></td>
                            <td>
                                <?php $badge = match($h['status']) {
                                    'success'    => 'bg-success',
                                    'partial'    => 'bg-warning text-dark',
                                    'failed'     => 'bg-danger',
                                    'processing' => 'bg-info',
                                    default      => 'bg-secondary',
                                }; ?>
                                <span class="badge <?= $badge ?>"><?= $h['status'] ?></span>
                            </td>
                            <td><small><?= htmlspecialchars($h['user_name'] ?? '-') ?></small></td>
                            <td><small class="text-muted"><?= htmlspecialchars($h['waktu_mulai'] ?? $h['created_at']) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (($total_pages ?? 1) > 1): ?>
    <div class="card-footer bg-white py-2">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Menampilkan <?= (($page_num - 1) * $per_page) + 1 ?>–<?= min($page_num * $per_page, $history_total) ?>
                dari <?= number_format($history_total) ?> record
            </small>
            <?php renderImportPagination($page_num, $total_pages, $per_page); ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
let previewPage = 1;

function loadPreviewPage(page) {
    previewPage = page || (previewPage + 1);
    const url = new URL(window.location.href);
    url.searchParams.set('action', 'ajax-preview');
    url.searchParams.set('page', previewPage);
    url.searchParams.set('per_page', 50);

    fetch(url.toString())
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                alert(res.message);
                return;
            }
            const tbody = document.getElementById('previewBody');
            const data = res.data;
            data.rows.forEach(function(rr) {
                const r = rr.row;
                const valid = rr.valid;
                const tr = document.createElement('tr');
                tr.className = valid ? '' : 'table-danger';
                tr.innerHTML = [
                    '<td>' + rr.row_number + '</td>',
                    '<td>' + esc(r.nmkec || r.kdkec || '-') + '</td>',
                    '<td>' + esc(r.nmdesa || r.kddesa || '-') + '</td>',
                    '<td>' + esc(r.kdsls || r.idsubsls || '-') + '</td>',
                    '<td>' + esc(r.nmsls || '-') + '</td>',
                    '<td>' + esc(r.nama_ketua || '-') + '</td>',
                    '<td class="text-end">' + num(r.kk) + '</td>',
                    '<td class="text-end">' + num(r.btt) + '</td>',
                    '<td class="text-end">' + num(r.bku) + '</td>',
                    '<td class="text-end">' + num(r.usaha) + '</td>',
                    '<td class="text-end fw-semibold">' + num(r.muatan) + '</td>',
                    '<td>' + (valid ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-exclamation-circle text-danger"></i>') + '</td>',
                ].join('');
                tbody.appendChild(tr);
            });
        })
        .catch(err => alert('Gagal memuat preview: ' + err.message));
}

function esc(s) {
    if (s === null || s === undefined) return '-';
    const d = document.createElement('div');
    d.textContent = String(s);
    return d.innerHTML;
}

function num(n) {
    return Number(n || 0).toLocaleString('id-ID');
}
</script>
