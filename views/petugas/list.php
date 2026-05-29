<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="fas fa-users me-2"></i>Manajemen Petugas</h5>
    <?php if (in_array($current_user['role'] ?? '', ['admin', 'operator'])): ?>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreate">
        <i class="fas fa-plus me-1"></i>Tambah Petugas
    </button>
    <?php endif; ?>
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
    <div class="col-md-2 col-4">
        <div class="card border-0 shadow-sm text-center py-2 h-100">
            <small class="text-muted"><?= htmlspecialchars(ROLE_LABELS[$rc['role']] ?? $rc['role']) ?></small>
            <div class="fw-bold"><?= number_format($rc['aktif']) ?>/<?= number_format($rc['total']) ?></div>
            <small class="text-success">aktif</small>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row mb-3">
    <div class="col-md-3">
        <select class="form-select form-select-sm" onchange="filterRole(this.value)">
            <option value="">Semua Role</option>
            <?php foreach (ROLE_LABELS as $roleKey => $roleLabel): ?>
                <option value="<?= $roleKey ?>" <?= $selected_role === $roleKey ? 'selected' : '' ?>>
                    <?= htmlspecialchars($roleLabel) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 datatable small">
                <thead class="table-light">
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Terakhir Login</th>
                        <?php if (in_array($current_user['role'] ?? '', ['admin', 'operator'])): ?>
                        <th class="text-end">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                        <td><span class="badge bg-<?= $u['role'] === 'admin' ? 'danger' : ($u['role'] === 'pml' ? 'warning text-dark' : ($u['role'] === 'pcl' ? 'success' : ($u['role'] === 'task_force' ? 'info' : 'primary'))) ?>"><?= htmlspecialchars(ROLE_LABELS[$u['role']] ?? $u['role']) ?></span></td>
                        <td>
                            <?php if ($u['status_akun'] === 'active'): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td><small class="text-muted"><?= htmlspecialchars($u['last_login_at'] ?? '-') ?></small></td>
                        <?php if (in_array($current_user['role'] ?? '', ['admin', 'operator'])): ?>
                        <td class="text-end">
                            <button class="btn btn-outline-primary btn-sm py-0" onclick="editPetugas(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['email'] ?? '')) ?>', '<?= $u['role'] ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-warning btn-sm py-0" onclick="resetPassword(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')">
                                <i class="fas fa-key"></i>
                            </button>
                            <form method="POST" action="?page=dashboard&sub=petugas&action=toggle-status" class="d-inline">
                                <?= $csrf_field ?? '' ?>
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="status" value="<?= $u['status_akun'] === 'active' ? 'inactive' : 'active' ?>">
                                <button class="btn btn-outline-<?= $u['status_akun'] === 'active' ? 'danger' : 'success' ?> btn-sm py-0" onclick="return confirm('<?= $u['status_akun'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?> petugas <?= htmlspecialchars($u['username']) ?>?')">
                                    <i class="fas fa-<?= $u['status_akun'] === 'active' ? 'ban' : 'check' ?>"></i>
                                </button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="<?= in_array($current_user['role'] ?? '', ['admin', 'operator']) ? '6' : '5' ?>" class="text-center text-muted py-4">Belum ada data petugas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Create -->
<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog modal-md">
        <form method="POST" action="?page=dashboard&sub=petugas&action=create">
            <?= $csrf_field ?? '' ?>
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold"><i class="fas fa-plus me-1"></i>Tambah Petugas</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Username</label>
                        <input type="text" name="username" class="form-control form-control-sm" required minlength="3">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Email</label>
                        <input type="email" name="email" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Password</label>
                        <input type="password" name="password" class="form-control form-control-sm" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Role</label>
                        <select name="role" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Role --</option>
                            <option value="pcl">PCL</option>
                            <option value="pml">PML</option>
                            <option value="task_force">Task Force</option>
                            <option value="operator">Operator</option>
                            <option value="pegawai">Pegawai</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-md">
        <form method="POST" action="?page=dashboard&sub=petugas&action=edit">
            <?= $csrf_field ?? '' ?>
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold"><i class="fas fa-edit me-1"></i>Edit Petugas</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id" value="0">
                    <div class="mb-3">
                        <label class="form-label small">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Role</label>
                        <select name="role" id="edit_role" class="form-select form-select-sm" required>
                            <option value="admin">Administrator</option>
                            <option value="operator">Operator</option>
                            <option value="pegawai">Pegawai</option>
                            <option value="mitra">Mitra</option>
                            <option value="pml">PML</option>
                            <option value="pcl">PCL</option>
                            <option value="task_force">Task Force</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Reset Password -->
<div class="modal fade" id="modalResetPassword" tabindex="-1">
    <div class="modal-dialog modal-md">
        <form method="POST" action="?page=dashboard&sub=petugas&action=reset-password">
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

function editPetugas(id, email, role) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_role').value = role;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

function resetPassword(id, username) {
    document.getElementById('reset_id').value = id;
    document.getElementById('reset_username').textContent = 'Reset password untuk: ' + username;
    new bootstrap.Modal(document.getElementById('modalResetPassword')).show();
}
</script>
