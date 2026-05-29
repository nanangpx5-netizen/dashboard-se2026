<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="fas fa-map-marker-alt me-2"></i>Data Wilayah</h5>
</div>

<?php if (!empty($flash)): ?>
    <?php foreach ($flash as $type => $message): ?>
        <div class="alert alert-<?= $type === 'error' ? 'danger' : ($type === 'success' ? 'success' : 'info') ?> alert-dismissible fade show py-2 small">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 datatable small">
                <thead class="table-light">
                    <tr>
                        <th>Kecamatan</th>
                        <th class="text-center">Kebutuhan PCL</th>
                        <th class="text-center">Kebutuhan PML</th>
                        <th class="text-center">Terisi PCL</th>
                        <th class="text-center">Terisi PML</th>
                        <th class="text-center">Total SLS</th>
                        <th class="text-center">Assign</th>
                        <th class="text-center">Selesai</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wilayah as $row): ?>
                        <?php
                        $progress = $row['total_sls'] > 0 ? round(($row['completed_sls'] / $row['total_sls']) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($row['nama_kecamatan']) ?>
                                <br><small class="text-muted"><?= htmlspecialchars($row['kode_kecamatan']) ?></small>
                            </td>
                            <td class="text-center"><?= number_format($row['kebutuhan_pcl']) ?></td>
                            <td class="text-center"><?= number_format($row['kebutuhan_pml']) ?></td>
                            <td class="text-center"><?= number_format($row['terisi_pcl']) ?></td>
                            <td class="text-center"><?= number_format($row['terisi_pml']) ?></td>
                            <td class="text-center fw-semibold"><?= number_format($row['total_sls'] ?? 0) ?></td>
                            <td class="text-center"><?= number_format($row['assigned_sls'] ?? 0) ?></td>
                            <td class="text-center">
                                <?php if ($progress > 0): ?>
                                    <span class="badge bg-success"><?= $progress ?>%</span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-outline-primary btn-sm py-0" onclick="editWilayah(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama_kecamatan'])) ?>', <?= $row['kebutuhan_pcl'] ?>, <?= $row['kebutuhan_pml'] ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($wilayah)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">Belum ada data wilayah.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEditWilayah" tabindex="-1">
    <div class="modal-dialog modal-md">
        <form method="POST" action="?page=dashboard&sub=wilayah&action=edit">
            <?= $csrf_field ?? '' ?>
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold"><i class="fas fa-edit me-1"></i>Edit Wilayah</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id" value="0">
                    <p class="small mb-3" id="edit_nama_kecamatan"></p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small">Kebutuhan PCL</label>
                            <input type="number" name="kebutuhan_pcl" id="edit_kebutuhan_pcl" class="form-control form-control-sm" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Kebutuhan PML</label>
                            <input type="number" name="kebutuhan_pml" id="edit_kebutuhan_pml" class="form-control form-control-sm" min="0" required>
                        </div>
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

<script>
function editWilayah(id, nama, kebutuhanPcl, kebutuhanPml) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama_kecamatan').textContent = nama;
    document.getElementById('edit_kebutuhan_pcl').value = kebutuhanPcl;
    document.getElementById('edit_kebutuhan_pml').value = kebutuhanPml;
    new bootstrap.Modal(document.getElementById('modalEditWilayah')).show();
}
</script>
