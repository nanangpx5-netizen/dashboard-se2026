<?php
/**
 * @var array $assignments
 * @var array $unassigned
 * @var array $kecamatan
 * @var array $desa_list
 * @var array $petugas
 * @var array $pcl_list
 * @var array $pml_list
 * @var array $tf_list
 * @var array $summary
 * @var array $petugas_load
 * @var array $filters
 * @var int $total_assigned
 * @var int $total_unassigned
 * @var int $page_num
 * @var int $per_page
 * @var int $total_pages
 * @var string $tab
 */

/**
 * Helper: render pagination links
 */
function renderPagination(int $page, int $totalPages, int $perPage, string $tab): void
{
    if ($totalPages <= 1) return;
    $qs = $_GET;
    unset($qs['hal']);
    $base = '?' . http_build_query(array_merge($qs, ['per_page' => $perPage, 'tab' => $tab]));
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
    <h5 class="mb-0 fw-bold"><i class="fas fa-tasks me-2"></i>Assignment Petugas</h5>
    <div class="d-flex gap-2">
        <a href="?page=dashboard&sub=assignment&action=template" class="btn btn-outline-success btn-sm">
            <i class="fas fa-download me-1"></i>Template
        </a>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalImportAssign">
            <i class="fas fa-file-excel me-1"></i>Import Excel
        </button>
        <button class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#modalLoad">
            <i class="fas fa-chart-simple me-1"></i>Beban Petugas
        </button>
    </div>
</div>

<?php if (!empty($flash)): ?>
    <?php foreach ($flash as $type => $message): ?>
        <div class="alert alert-<?= $type === 'error' ? 'danger' : ($type === 'success' ? 'success' : 'info') ?> alert-dismissible fade show py-2 small">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- ─── Summary Cards ─────────────────────────────────────────────── -->
<div class="row g-2 mb-3">
    <div class="col-md-2 col-4">
        <div class="card border-0 shadow-sm text-center py-2 h-100">
            <small class="text-muted">Total SLS</small>
            <span class="fw-bold fs-5"><?= number_format($summary['total_sls'] ?? 0) ?></span>
        </div>
    </div>
    <div class="col-md-2 col-4">
        <div class="card border-0 shadow-sm text-center py-2 h-100">
            <small class="text-muted">Assign</small>
            <span class="fw-bold fs-5 text-success"><?= number_format($summary['total_assign'] ?? 0) ?></span>
        </div>
    </div>
    <div class="col-md-2 col-4">
        <div class="card border-0 shadow-sm text-center py-2 h-100">
            <small class="text-muted">Belum Assign</small>
            <span class="fw-bold fs-5 text-warning"><?= number_format($summary['belum_assign'] ?? 0) ?></span>
        </div>
    </div>
    <div class="col-md-2 col-4">
        <div class="card border-0 shadow-sm text-center py-2 h-100">
            <small class="text-muted">Proses</small>
            <span class="fw-bold fs-5 text-primary"><?= number_format($summary['status_proses'] ?? 0) ?></span>
        </div>
    </div>
    <div class="col-md-2 col-4">
        <div class="card border-0 shadow-sm text-center py-2 h-100">
            <small class="text-muted">Selesai</small>
            <span class="fw-bold fs-5 text-success"><?= number_format($summary['status_selesai'] ?? 0) ?></span>
        </div>
    </div>
    <div class="col-md-2 col-4">
        <div class="card border-0 shadow-sm text-center py-2 h-100">
            <small class="text-muted">PCL/PML/TF Aktif</small>
            <span class="fw-bold fs-5 text-info"><?= number_format(($summary['pcl_aktif'] ?? 0) + ($summary['pml_aktif'] ?? 0) + ($summary['tf_aktif'] ?? 0)) ?></span>
        </div>
    </div>
</div>

<!-- ─── Filter Bar ────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="?page=dashboard&sub=assignment" class="row g-2 align-items-end" id="filterForm">
            <input type="hidden" name="page" value="dashboard">
            <input type="hidden" name="sub" value="assignment">
            <input type="hidden" name="hal" value="1">
            <input type="hidden" name="tab" id="filterTab" value="<?= htmlspecialchars($tab) ?>">
            <div class="col-md-3">
                <label class="form-label small mb-0">Kecamatan</label>
                <select name="kdkec" class="form-select form-select-sm" onchange="filterChanged()">
                    <option value="">Semua Kecamatan</option>
                    <?php foreach ($kecamatan as $k): ?>
                        <option value="<?= htmlspecialchars($k['kdkec']) ?>"
                            <?= ($filters['kdkec'] ?? '') === $k['kdkec'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['nmkec']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Desa</label>
                <select name="kddesa" class="form-select form-select-sm" onchange="filterChanged()">
                    <option value="">Semua Desa</option>
                    <?php foreach ($desa_list as $d): ?>
                        <option value="<?= htmlspecialchars($d['kddesa']) ?>"
                            <?= ($filters['kddesa'] ?? '') === $d['kddesa'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['nmdesa']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Status</label>
                <select name="status" class="form-select form-select-sm" onchange="filterChanged()">
                    <option value="">Semua Status</option>
                    <option value="belum" <?= ($filters['status'] ?? '') === 'belum' ? 'selected' : '' ?>>Belum</option>
                    <option value="proses" <?= ($filters['status'] ?? '') === 'proses' ? 'selected' : '' ?>>Proses</option>
                    <option value="selesai" <?= ($filters['status'] ?? '') === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Cari</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="SLS / petugas..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-1">
                <label class="form-label small mb-0">Tampil</label>
                <select name="per_page" class="form-select form-select-sm" onchange="filterChanged()">
                    <option value="10" <?= $per_page === 10 ? 'selected' : '' ?>>10</option>
                    <option value="25" <?= $per_page === 25 ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= $per_page === 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $per_page === 100 ? 'selected' : '' ?>>100</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-fill"><i class="fas fa-search"></i></button>
                <a href="?page=dashboard&sub=assignment" class="btn btn-sm btn-outline-secondary flex-fill">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- ─── Tabs ──────────────────────────────────────────────────────── -->
<ul class="nav nav-tabs mb-3" id="assignTab">
    <li class="nav-item">
        <button class="nav-link small" data-bs-toggle="tab" data-bs-target="#tabAssigned">
            <i class="fas fa-check-circle me-1"></i>Sudah Assign
            <span class="badge bg-secondary"><?= number_format($total_assigned ?? 0) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link active small" data-bs-toggle="tab" data-bs-target="#tabUnassigned">
            <i class="fas fa-clock me-1"></i>Belum Assign
            <span class="badge bg-warning text-dark"><?= number_format($total_unassigned ?? 0) ?></span>
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- ═══════ TAB: SUDAH ASSIGN ═══════ -->
    <div class="tab-pane fade" id="tabAssigned">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 small" id="tableAssigned">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px">No</th>
                                <th>SLS</th>
                                <th>Desa</th>
                                <th>Kecamatan</th>
                                <th class="text-center">Muatan</th>
                                <th>PCL</th>
                                <th>PML</th>
                                <th>Task Force</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; ?>
                            <?php foreach ($assignments as $r): ?>
                            <tr>
                                <td class="text-center text-muted"><?= $i++ ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($r['nmsls'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($r['nmdesa'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($r['nmkec'] ?? '-') ?></td>
                                <td class="text-center"><?= number_format($r['muatan'] ?? 0) ?></td>
                                <td><span class="badge bg-success bg-opacity-10 text-success"><?= htmlspecialchars($r['pencacah'] ?? '-') ?></span></td>
                                <td><span class="badge bg-warning bg-opacity-10 text-warning"><?= htmlspecialchars($r['pengawas'] ?? '-') ?></span></td>
                                <td><span class="badge bg-info bg-opacity-10 text-info"><?= htmlspecialchars($r['task_force'] ?? '-') ?></span></td>
                                <td class="text-center">
                                    <form method="POST" action="?page=dashboard&sub=assignment&action=status" class="d-inline">
                                        <?= $csrf_field ?? '' ?>
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <select name="status" class="form-select form-select-sm" style="width:auto;display:inline-block;width:90px;" onchange="this.form.submit()">
                                            <option value="belum" <?= $r['status'] === 'belum' ? 'selected' : '' ?>>Belum</option>
                                            <option value="proses" <?= $r['status'] === 'proses' ? 'selected' : '' ?>>Proses</option>
                                            <option value="selesai" <?= $r['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-outline-primary btn-sm py-0" title="Edit" onclick="editAssign(<?= $r['id'] ?>, <?= $r['sipw_id'] ?>, <?= json_encode($r['pencacah_id']) ?>, <?= json_encode($r['pengawas_id']) ?>, <?= json_encode($r['task_force_id']) ?>)">
                                        <i class="fas fa-user-pen"></i>
                                    </button>
                                    <form method="POST" action="?page=dashboard&sub=assignment&action=remove" class="d-inline" onsubmit="return confirm('Hapus assignment ini?')">
                                        <?= $csrf_field ?? '' ?>
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <button class="btn btn-outline-danger btn-sm py-0" title="Hapus"><i class="fas fa-trash-can"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($assignments)): ?>
                            <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php renderPagination($page_num, $total_pages, $per_page, 'assigned'); ?>
            </div>
        </div>
    </div>

    <!-- ═══════ TAB: BELUM ASSIGN ═══════ -->
    <div class="tab-pane fade show active" id="tabUnassigned">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 small" id="tableUnassigned">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px">No</th>
                                <th>SLS</th>
                                <th>Desa</th>
                                <th>Kecamatan</th>
                                <th>Ketua SLS</th>
                                <th class="text-center">KK</th>
                                <th class="text-center">BTT</th>
                                <th class="text-center">Muatan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; ?>
                            <?php foreach ($unassigned as $r): ?>
                            <tr>
                                <td class="text-center text-muted"><?= $i++ ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($r['nmsls'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($r['nmdesa'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($r['nmkec'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($r['nama_ketua'] ?? '-') ?></td>
                                <td class="text-center"><?= number_format($r['kk'] ?? 0) ?></td>
                                <td class="text-center"><?= number_format($r['btt'] ?? 0) ?></td>
                                <td class="text-center fw-semibold"><?= number_format($r['muatan'] ?? 0) ?></td>
                                <td class="text-center">
                                    <button class="btn btn-primary btn-sm py-0" onclick="openAssign(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['nmsls'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($r['nmdesa'] ?? '')) ?>')">
                                        <i class="fas fa-user-plus me-1"></i>Assign
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($unassigned)): ?>
                            <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php renderPagination($page_num, $total_pages, $per_page, 'unassigned'); ?>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════ -->
<!-- MODAL: Single Assign                                             -->
<!-- ══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalAssign" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="?page=dashboard&sub=assignment&action=assign">
            <?= $csrf_field ?? '' ?>
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold" id="modalAssignTitle"><i class="fas fa-user-plus me-1"></i>Assign Petugas</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="sipw_id" id="assign_sipw_id" value="0">
                    <div class="row mb-3">
                        <div class="col">
                            <small class="text-muted" id="assign_sls_info">Pilih SLS terlebih dahulu</small>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Pencacah (PCL)</label>
                            <select name="pencacah_id" class="form-select form-select-sm petugas-select" data-role="pcl">
                                <option value="">-- Pilih PCL --</option>
                                <?php foreach ($pcl_list as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Pengawas (PML)</label>
                            <select name="pengawas_id" class="form-select form-select-sm petugas-select" data-role="pml">
                                <option value="">-- Pilih PML --</option>
                                <?php foreach ($pml_list as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Task Force</label>
                            <select name="task_force_id" class="form-select form-select-sm petugas-select" data-role="tf">
                                <option value="">-- Pilih Task Force --</option>
                                <?php foreach ($tf_list as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i>Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════ -->
<!-- MODAL: Edit Assign                                               -->
<!-- ══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="?page=dashboard&sub=assignment&action=edit">
            <?= $csrf_field ?? '' ?>
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold"><i class="fas fa-user-pen me-1"></i>Edit Assignment</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="sipw_id" id="edit_sipw_id" value="0">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Pencacah (PCL)</label>
                            <select name="pencacah_id" id="edit_pencacah_id" class="form-select form-select-sm">
                                <option value="">-- Pilih PCL --</option>
                                <?php foreach ($pcl_list as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Pengawas (PML)</label>
                            <select name="pengawas_id" id="edit_pengawas_id" class="form-select form-select-sm">
                                <option value="">-- Pilih PML --</option>
                                <?php foreach ($pml_list as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Task Force</label>
                            <select name="task_force_id" id="edit_task_force_id" class="form-select form-select-sm">
                                <option value="">-- Pilih Task Force --</option>
                                <?php foreach ($tf_list as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i>Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════ -->
<!-- MODAL: Import Assign Excel                                      -->
<!-- ══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalImportAssign" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="?page=dashboard&sub=assignment&action=import_upload" enctype="multipart/form-data">
            <?= $csrf_field ?? '' ?>
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold"><i class="fas fa-file-excel me-1"></i>Import Assignment dari Excel</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small">
                        <i class="fas fa-info-circle me-1"></i>
                        File Excel harus memiliki kolom: <strong>nmsls</strong>, <strong>nmdesa</strong>, <strong>nmkec</strong>,
                        dan opsional <strong>pcl</strong>, <strong>pml</strong>, <strong>task_force</strong> (username).
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">File Excel</label>
                        <input type="file" name="import_file" class="form-control form-control-sm" accept=".xlsx,.xls,.csv" required>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-upload me-1"></i>Upload & Proses</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($import_preview)): ?>
<div class="card border-0 shadow-sm mb-3 border-start border-success border-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <small class="text-success fw-semibold"><i class="fas fa-file-excel me-1"></i>Preview Import</small>
                <span class="mx-2 text-muted">|</span>
                <small class="text-muted"><?= $import_preview['total_rows'] ?? 0 ?> baris</small>
                <?php if ($import_preview['has_pcl'] ?? false): ?>
                    <span class="badge bg-success bg-opacity-10 text-success ms-1">PCL</span>
                <?php endif; ?>
                <?php if ($import_preview['has_pml'] ?? false): ?>
                    <span class="badge bg-warning bg-opacity-10 text-warning ms-1">PML</span>
                <?php endif; ?>
                <?php if ($import_preview['has_tf'] ?? false): ?>
                    <span class="badge bg-info bg-opacity-10 text-info ms-1">TF</span>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-1">
                <form method="POST" action="?page=dashboard&sub=assignment&action=import_process">
                    <?= $csrf_field ?? '' ?>
                    <button class="btn btn-sm btn-success" onclick="return confirm('Proses import assignment?')">
                        <i class="fas fa-check me-1"></i>Proses Import
                    </button>
                </form>
                <a href="?page=dashboard&sub=assignment" class="btn btn-sm btn-outline-secondary">Batal</a>
            </div>
        </div>
        <?php if (!empty($import_preview['sample'])): ?>
        <div class="table-responsive mt-2" style="max-height:200px">
            <table class="table table-sm mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>SLS</th>
                        <th>Desa</th>
                        <th>Kecamatan</th>
                        <?php if ($import_preview['has_pcl'] ?? false): ?><th>PCL</th><?php endif; ?>
                        <?php if ($import_preview['has_pml'] ?? false): ?><th>PML</th><?php endif; ?>
                        <?php if ($import_preview['has_tf'] ?? false): ?><th>TF</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($import_preview['sample'] as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['nmsls'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($s['nmdesa'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($s['nmkec'] ?? '-') ?></td>
                        <?php if ($import_preview['has_pcl'] ?? false): ?><td><?= htmlspecialchars($s['pcl'] ?? '-') ?></td><?php endif; ?>
                        <?php if ($import_preview['has_pml'] ?? false): ?><td><?= htmlspecialchars($s['pml'] ?? '-') ?></td><?php endif; ?>
                        <?php if ($import_preview['has_tf'] ?? false): ?><td><?= htmlspecialchars($s['task_force'] ?? '-') ?></td><?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════ -->
<!-- MODAL: Beban Petugas (Load)                                    -->
<!-- ══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalLoad" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-semibold"><i class="fas fa-chart-simple me-1"></i>Beban Kerja Petugas</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Petugas</th>
                                <th>Role</th>
                                <th class="text-center">Sebagai PCL</th>
                                <th class="text-center">Sebagai PML</th>
                                <th class="text-center">Sebagai TF</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Selesai PCL</th>
                                <th class="text-center">Selesai PML</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($petugas_load as $pl): ?>
                            <tr>
                                <td><?= htmlspecialchars($pl['username']) ?></td>
                                <td><span class="badge bg-<?= $pl['role'] === 'admin' ? 'danger' : ($pl['role'] === 'pml' ? 'warning text-dark' : ($pl['role'] === 'pcl' ? 'success' : 'info')) ?>"><?= $pl['role'] ?></span></td>
                                <td class="text-center"><?= number_format($pl['as_pencacah']) ?></td>
                                <td class="text-center"><?= number_format($pl['as_pengawas']) ?></td>
                                <td class="text-center"><?= number_format($pl['as_task_force'] ?? 0) ?></td>
                                <td class="text-center fw-semibold"><?= number_format($pl['as_pencacah'] + $pl['as_pengawas'] + ($pl['as_task_force'] ?? 0)) ?></td>
                                <td class="text-center text-success"><?= number_format($pl['selesai_pencacah']) ?></td>
                                <td class="text-center text-warning"><?= number_format($pl['selesai_pengawas']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($petugas_load)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data assignment.</td></tr>
                            <?php endif; ?>
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

