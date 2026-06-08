<?php
/** @var array $ranking */
/** @var array $kecamatan */
/** @var array $roles */
/** @var string $chartLabels */
/** @var string $chartMuatan */
/** @var string|null $filterRole */
/** @var string|null $filterKdkec */
?>
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Beban Kerja Petugas</h5>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form class="row g-3 align-items-end" method="GET">
                <input type="hidden" name="page" value="dashboard">
                <input type="hidden" name="sub" value="workload">

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Role</label>
                    <select name="role" class="form-select">
                        <option value="">Semua Role</option>
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['role'] ?>" <?= $filterRole === $r['role'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ROLE_LABELS[$r['role']] ?? $r['role']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Kecamatan</label>
                    <?php if (!empty($kecamatan_scope)): ?>
                        <?php
                        $scopeKecName = '';
                        foreach ($kecamatan as $kk) {
                            if (substr($kecamatan_scope, -3) === $kk['kdkec']) {
                                $scopeKecName = $kk['nmkec'];
                                break;
                            }
                        }
                        ?>
                        <input type="hidden" name="kdkec" value="<?= htmlspecialchars(substr($kecamatan_scope, -3)) ?>">
                        <input type="text" class="form-control" value="<?= htmlspecialchars($scopeKecName) ?>" readonly disabled>
                    <?php else: ?>
                    <select name="kdkec" class="form-select">
                        <option value="">Semua Kecamatan</option>
                        <?php foreach ($kecamatan as $k): ?>
                        <option value="<?= $k['kdkec'] ?>" <?= $filterKdkec === $k['kdkec'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['nmkec']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-se2026">
                        <i class="fas fa-filter me-1"></i>Terapkan
                    </button>
                    <a href="?page=dashboard&sub=workload" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <canvas id="workloadChart" height="100"></canvas>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle" id="workloadTable">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width:50px;">#</th>
                            <th>Nama Petugas</th>
                            <th style="width:100px;">Role</th>
                            <th class="text-end" style="width:90px;">SLS</th>
                            <th class="text-end" style="width:90px;">KK</th>
                            <th class="text-end" style="width:90px;">Usaha</th>
                            <th class="text-end" style="width:90px;">Muatan</th>
                            <th class="text-center" style="width:60px;">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ranking)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                Belum ada data
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php $no = 1; ?>
                        <?php foreach ($ranking as $r): ?>
                        <tr>
                            <td class="text-center fw-bold"><?= $no++ ?></td>
                            <td><?= htmlspecialchars($r['username']) ?></td>
                            <td>
                                <span class="badge bg-<?= $r['role'] === 'pcl' ? 'success' : ($r['role'] === 'pml' ? 'warning text-dark' : 'info') ?> rounded-pill">
                                    <?= htmlspecialchars(ROLE_LABELS[$r['role']] ?? $r['role']) ?>
                                </span>
                            </td>
                            <td class="text-end fw-semibold"><?= number_format($r['jumlah_sls']) ?></td>
                            <td class="text-end"><?= number_format($r['total_kk']) ?></td>
                            <td class="text-end"><?= number_format($r['total_usaha']) ?></td>
                            <td class="text-end fw-semibold"><?= number_format($r['total_muatan']) ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary btn-detail"
                                        data-id="<?= $r['id'] ?>"
                                        data-role="<?= $r['role'] ?>"
                                        data-username="<?= htmlspecialchars($r['username']) ?>"
                                        title="Lihat Detail">
                                    <i class="fas fa-search"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="detailModalLabel">Detail Petugas</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3" id="detailSummary"></div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" id="detailTable">
                        <thead class="table-light">
                            <tr>
                                <th>Kecamatan</th>
                                <th>Desa</th>
                                <th>SLS</th>
                                <th>KK</th>
                                <th>Usaha</th>
                                <th>Muatan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
document.querySelectorAll('.btn-detail').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.dataset.id;
        var role = this.dataset.role;
        var username = this.dataset.username;

        document.getElementById('detailModalLabel').textContent = 'Detail: ' + username;
        document.querySelector('#detailTable tbody').innerHTML =
            '<tr><td colspan="7" class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin me-2"></i>Memuat...</td></tr>';

        var modal = new bootstrap.Modal(document.getElementById('detailModal'));
        modal.show();

        fetch('?page=dashboard&sub=workload&action=detail&id=' + id + '&role=' + role)
            .then(function(res) { return res.json(); })
            .then(function(res) {
                if (!res.success) {
                    document.querySelector('#detailTable tbody').innerHTML =
                        '<tr><td colspan="7" class="text-danger text-center">Gagal memuat data</td></tr>';
                    return;
                }

                var summary = res.summary;
                document.getElementById('detailSummary').innerHTML =
                    '<div class="col-md-3"><div class="p-2 bg-light rounded text-center"><small class="text-muted d-block">SLS</small><span class="fw-bold fs-6">' + summary.jumlah_sls + '</span></div></div>' +
                    '<div class="col-md-3"><div class="p-2 bg-light rounded text-center"><small class="text-muted d-block">KK</small><span class="fw-bold fs-6">' + summary.total_kk.toLocaleString('id-ID') + '</span></div></div>' +
                    '<div class="col-md-3"><div class="p-2 bg-light rounded text-center"><small class="text-muted d-block">Usaha</small><span class="fw-bold fs-6">' + summary.total_usaha.toLocaleString('id-ID') + '</span></div></div>' +
                    '<div class="col-md-3"><div class="p-2 bg-light rounded text-center"><small class="text-muted d-block">Muatan</small><span class="fw-bold fs-6">' + summary.total_muatan.toLocaleString('id-ID') + '</span></div></div>';

                var html = '';
                res.data.forEach(function(d) {
                    var badge = d.status === 'selesai' ? 'success' : (d.status === 'proses' ? 'warning text-dark' : 'secondary');
                    html += '<tr>' +
                        '<td>' + escHtml(d.nmkec) + '</td>' +
                        '<td>' + escHtml(d.nmdesa) + '</td>' +
                        '<td>' + escHtml(d.nmsls) + '</td>' +
                        '<td class="text-end">' + Number(d.kk).toLocaleString('id-ID') + '</td>' +
                        '<td class="text-end">' + Number(d.usaha).toLocaleString('id-ID') + '</td>' +
                        '<td class="text-end">' + Number(d.muatan).toLocaleString('id-ID') + '</td>' +
                        '<td><span class="badge bg-' + badge + ' rounded-pill">' + escHtml(d.status) + '</span></td>' +
                        '</tr>';
                });
                document.querySelector('#detailTable tbody').innerHTML = html;
            })
            .catch(function() {
                document.querySelector('#detailTable tbody').innerHTML =
                    '<tr><td colspan="7" class="text-danger text-center">Terjadi kesalahan</td></tr>';
            });
    });
});
</script>

<script>
(function() {
    var ctx = document.getElementById('workloadChart');
    if (!ctx) return;
    var labels = <?= htmlspecialchars($chartLabels ?: '[]', ENT_QUOTES, 'UTF-8') ?>;
    var data   = <?= htmlspecialchars($chartMuatan ?: '[]', ENT_QUOTES, 'UTF-8') ?>;

    if (labels.length === 0) {
        ctx.parentNode.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-chart-bar fa-2x mb-2 d-block"></i>Belum ada data untuk ditampilkan</div>';
        return;
    }

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Muatan',
                data: data,
                backgroundColor: 'rgba(244, 123, 32, 0.7)',
                borderColor: 'rgba(244, 123, 32, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return Number(ctx.raw).toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        callback: function(v) { return Number(v).toLocaleString('id-ID'); }
                    }
                }
            }
        }
    });
})();
</script>
