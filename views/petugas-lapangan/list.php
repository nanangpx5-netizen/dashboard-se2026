<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="fas fa-people-carry-box me-2"></i>Petugas Lapangan (PCL/PML/TF)</h5>
    <div class="d-flex gap-2">
        <a href="?page=dashboard&sub=petugas-lapangan&action=template" class="btn btn-outline-success btn-sm">
            <i class="fas fa-download me-1"></i>Template
        </a>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalImport">
            <i class="fas fa-file-excel me-1"></i>Import Excel
        </button>
        <button class="btn btn-se2026 btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreate">
            <i class="fas fa-plus me-1"></i>Tambah Petugas
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

<?php if (!empty($role_counts)): ?>
<div class="row g-2 mb-3">
    <?php foreach ($role_counts as $rc): ?>
    <div class="col-md-4 col-4">
        <div class="card border-0 shadow-sm text-center py-2 h-100">
            <small class="text-muted"><?= htmlspecialchars(ROLE_LABELS[$rc['role']] ?? $rc['role']) ?></small>
            <div class="fw-bold fs-5"><?= number_format($rc['aktif']) ?>/<?= number_format($rc['total']) ?></div>
            <small class="text-success">aktif</small>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($import_preview)): ?>
<div class="card border-0 shadow-sm mb-3 border-start border-success border-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <small class="text-success fw-semibold"><i class="fas fa-file-excel me-1"></i>Preview Import</small>
                <span class="mx-2 text-muted">|</span>
                <small class="text-muted"><?= $import_preview['total_rows'] ?? 0 ?> baris</small>
            </div>
            <div class="d-flex gap-1">
                <form method="POST" action="?page=dashboard&sub=petugas-lapangan&action=import_process">
                    <?= $csrf_field ?? '' ?>
                    <button class="btn btn-sm btn-success" onclick="return confirm('Proses import <?= $import_preview['total_rows'] ?> petugas?')">
                        <i class="fas fa-check me-1"></i>Proses Import
                    </button>
                </form>
                <a href="?page=dashboard&sub=petugas-lapangan" class="btn btn-sm btn-outline-secondary">Batal</a>
            </div>
        </div>
        <?php if (!empty($import_preview['sample'])): ?>
        <div class="table-responsive mt-2" style="max-height:200px">
            <table class="table table-sm mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($import_preview['sample'] as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['namaLengkap'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($s['username'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($s['email'] ?? '-') ?></td>
                        <td><span class="badge bg-<?= $s['role'] === 'pml' ? 'warning text-dark' : ($s['role'] === 'pcl' ? 'success' : 'info') ?>"><?= strtoupper($s['role']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="row mb-3">
    <div class="col-md-3">
        <select class="form-select form-select-sm" onchange="filterRole(this.value)">
            <option value="">Semua Role</option>
            <option value="pcl" <?= $selected_role === 'pcl' ? 'selected' : '' ?>>PCL</option>
            <option value="pml" <?= $selected_role === 'pml' ? 'selected' : '' ?>>PML</option>
            <option value="task_force" <?= $selected_role === 'task_force' ? 'selected' : '' ?>>Task Force</option>
        </select>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 datatable no-datatable small" id="tablePetugasLapangan">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:40px">No</th>
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Terakhir Login</th>
                        <th class="text-end" style="width:140px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="text-center text-muted"></td>
                        <td class="fw-semibold"><?= htmlspecialchars($u['nama_lengkap'] ?: $u['username']) ?></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                        <td>
                            <span class="badge bg-<?= $u['role'] === 'pml' ? 'warning text-dark' : ($u['role'] === 'pcl' ? 'success' : 'info') ?>">
                                <?= htmlspecialchars(ROLE_LABELS[$u['role']] ?? $u['role']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['status_akun'] === 'active'): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td><small class="text-muted"><?= htmlspecialchars($u['last_login_at'] ?? '-') ?></small></td>
                        <td class="text-end">
                            <button class="btn btn-outline-primary btn-sm py-0" title="Edit"
                                onclick="editPetugas(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nama_lengkap'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($u['email'] ?? '')) ?>', '<?= $u['role'] ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-warning btn-sm py-0" title="Reset Password"
                                onclick="resetPassword(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nama_lengkap'] ?: $u['username'])) ?>')">
                                <i class="fas fa-key"></i>
                            </button>
                            <form method="POST" action="?page=dashboard&sub=petugas-lapangan&action=toggle-status" class="d-inline">
                                <?= $csrf_field ?? '' ?>
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="status" value="<?= $u['status_akun'] === 'active' ? 'inactive' : 'active' ?>">
                                <button class="btn btn-outline-<?= $u['status_akun'] === 'active' ? 'danger' : 'success' ?> btn-sm py-0" title="<?= $u['status_akun'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>"
                                    onclick="return confirm('<?= $u['status_akun'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?> <?= htmlspecialchars($u['nama_lengkap'] ?: $u['username']) ?>?')">
                                    <i class="fas fa-<?= $u['status_akun'] === 'active' ? 'ban' : 'check' ?>"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Create -->
<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog modal-md">
        <form method="POST" action="?page=dashboard&sub=petugas-lapangan&action=create">
            <?= $csrf_field ?? '' ?>
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold"><i class="fas fa-plus me-1"></i>Tambah Petugas Lapangan</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama_lengkap" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control form-control-sm" required minlength="3">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control form-control-sm" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Role --</option>
                            <option value="pcl">PCL</option>
                            <option value="pml">PML</option>
                            <option value="task_force">Task Force</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-se2026">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-md">
        <form method="POST" action="?page=dashboard&sub=petugas-lapangan&action=edit">
            <?= $csrf_field ?? '' ?>
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold"><i class="fas fa-edit me-1"></i>Edit Petugas Lapangan</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id" value="0">
                    <div class="mb-3">
                        <label class="form-label small">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" id="edit_nama_lengkap" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Role</label>
                        <select name="role" id="edit_role" class="form-select form-select-sm" required>
                            <option value="pcl">PCL</option>
                            <option value="pml">PML</option>
                            <option value="task_force">Task Force</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-se2026">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Reset Password -->
<div class="modal fade" id="modalResetPassword" tabindex="-1">
    <div class="modal-dialog modal-md">
        <form method="POST" action="?page=dashboard&sub=petugas-lapangan&action=reset-password">
            <?= $csrf_field ?? '' ?>
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold"><i class="fas fa-key me-1"></i>Reset Password</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="reset_id" value="0">
                    <p class="small" id="reset_username"></p>
                    <div class="mb-3">
                        <label class="form-label small">Password Baru</label>
                        <input type="password" name="password" class="form-control form-control-sm" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-warning">Reset</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function filterRole(role) {
    const url = new URL(window.location.href);
    if (role) url.searchParams.set('role', role);
    else url.searchParams.delete('role');
    window.location.href = url.toString();
}

function editPetugas(id, namaLengkap, email, role) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama_lengkap').value = namaLengkap;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_role').value = role;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

function resetPassword(id, nama) {
    document.getElementById('reset_id').value = id;
    document.getElementById('reset_username').textContent = 'Reset password untuk: ' + nama;
    new bootstrap.Modal(document.getElementById('modalResetPassword')).show();
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.alert-dismissible').forEach(function (el) {
        setTimeout(function () {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert.close();
        }, 4000);
    });
});
</script>

<!-- Modal: Import Excel -->
<div class="modal fade" id="modalImport" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="?page=dashboard&sub=petugas-lapangan&action=import_upload" enctype="multipart/form-data">
            <?= $csrf_field ?? '' ?>
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold"><i class="fas fa-file-excel me-1"></i>Import Petugas dari Excel</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small">
                        <i class="fas fa-info-circle me-1"></i>
                        File Excel harus memiliki kolom: <strong>Nama Lengkap</strong>, <strong>Username</strong>, <strong>Email</strong>, <strong>Password</strong>, <strong>Role</strong> (pcl/pml/task_force).
                        <br>Download template terlebih dahulu untuk format yang benar.
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
