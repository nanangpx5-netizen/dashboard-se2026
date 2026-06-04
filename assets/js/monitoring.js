/**
 * Monitoring Wilayah — DataTables server-side + widgets interaktif
 * DataTables, kecamatan summary, desa rincian, SLS & non-SLS tabs, search, auto-refresh
 */
var monitoringFilters = {
    kdkec: '',
    kddesa: '',
    pencacah: '',
    pengawas: '',
    task_force: '',
    status: '',
};

function getFilterParams() {
    return {
        kdkec: document.getElementById('filterKdkec').value,
        kddesa: document.getElementById('filterKddesa').value,
        pencacah: document.getElementById('filterPencacah').value,
        pengawas: document.getElementById('filterPengawas').value,
        task_force: document.getElementById('filterTaskForce').value,
    };
}

function onFilterChange() {
    var dt = $('#tableMonitoring').DataTable();
    var f = getFilterParams();
    dt.ajax.url(buildDataUrl(f));
    dt.ajax.reload();
    updateCascadeDesa();
}

function buildDataUrl(filters) {
    var params = new URLSearchParams({ page: 'dashboard', sub: 'monitoring', action: 'data' });
    Object.entries(filters).forEach(function (_a) {
        var key = _a[0], val = _a[1];
        if (val) params.set(key, val);
    });
    return '?' + params.toString();
}

function buildExportUrl() {
    var f = getFilterParams();
    var params = new URLSearchParams({ page: 'dashboard', sub: 'monitoring', action: 'export' });
    Object.entries(f).forEach(function (_a) {
        var key = _a[0], val = _a[1];
        if (val) params.set(key, val);
    });
    return '?' + params.toString();
}

function exportExcel() {
    window.location.href = buildExportUrl();
}

function resetFilters() {
    document.getElementById('filterKdkec').value = '';
    document.getElementById('filterKddesa').value = '';
    document.getElementById('filterPencacah').value = '';
    document.getElementById('filterPengawas').value = '';
    document.getElementById('filterTaskForce').value = '';
    document.getElementById('filterStatus').value = '';
    onFilterChange();
}

function updateCascadeDesa() {
    var kdkec = document.getElementById('filterKdkec').value;
    var desaSelect = document.getElementById('filterKddesa');
    var currentVal = desaSelect.value;
    desaSelect.innerHTML = '<option value="">Semua Desa</option>';
    if (!kdkec) return;
    fetch('?page=dashboard&sub=monitoring&action=filters&kdkec=' + encodeURIComponent(kdkec))
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.success) return;
            res.data.forEach(function (d) {
                var opt = document.createElement('option');
                opt.value = d.kddesa;
                opt.textContent = d.nmdesa;
                if (d.kddesa === currentVal) opt.selected = true;
                desaSelect.appendChild(opt);
            });
        });
}

// ─── Kecamatan Summary Widget ──────────────────────────────────

function loadKecamatanSummary() {
    var el = document.getElementById('kecamatanCards');
    el.innerHTML = '<div class="col-12 text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Memuat data...</div>';

    fetch('?page=dashboard&sub=monitoring&action=kecamatan-summary')
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.success || !res.data.length) {
                el.innerHTML = '<div class="col-12 text-center text-muted py-4">Belum ada data kecamatan ter-assign</div>';
                document.getElementById('kecTotalAssigned').textContent = '0';
                document.getElementById('kecTotal').textContent = '0';
                return;
            }
            renderKecamatanCards(el, res.data);
        })
        .catch(function () {
            el.innerHTML = '<div class="col-12 text-center text-danger py-4">Gagal memuat data</div>';
        });
}

function renderKecamatanCards(container, data) {
    var total = data.length;
    var assigned = data.filter(function (d) { return parseInt(d.assigned_sls) > 0; }).length;
    document.getElementById('kecTotalAssigned').textContent = assigned;
    document.getElementById('kecTotal').textContent = total;

    var html = '';
    data.forEach(function (d) {
        var a = parseInt(d.assigned_sls) || 0;
        var p = parseInt(d.proses_sls) || 0;
        var s = parseInt(d.selesai_sls) || 0;
        var t = parseInt(d.total_sls) || 1;
        var pct = Math.round((a / t) * 100);
        var badge = a > 0 ? 'bg-success' : 'bg-secondary';
        var statusText = a > 0 ? a + ' assign' : 'Belum';
        var lastUp = d.last_update ? d.last_update : '-';

        html += '<div class="col-xl-2 col-lg-3 col-md-4 col-6">';
        html += '<div class="card border-0 shadow-sm h-100">';
        html += '<div class="card-body p-2">';
        html += '<div class="d-flex justify-content-between align-items-center mb-1">';
        html += '<small class="fw-semibold text-truncate">' + escHtml(d.nmkec) + '</small>';
        html += '<span class="badge ' + badge + ' rounded-pill" style="font-size:9px">' + statusText + '</span>';
        html += '</div>';
        html += '<div class="progress mb-1" style="height:4px"><div class="progress-bar bg-se2026" style="width:' + pct + '%"></div></div>';
        html += '<div class="d-flex justify-content-between small text-muted">';
        html += '<span>' + a + '/' + t + ' SLS</span>';
        html += '<span>' + pct + '%</span>';
        html += '</div>';
        html += '<small class="text-muted d-block" style="font-size:9px; line-height:1.2"><i class="far fa-clock me-1"></i>' + lastUp + '</small>';
        html += '</div></div></div>';
    });
    container.innerHTML = html;
}

// ─── Desa Summary Widget ───────────────────────────────────────

function loadDesaSummary() {
    var kdkec = document.getElementById('desaKdkecFilter').value;
    var el = document.getElementById('desaSummaryContainer');
    if (!kdkec) {
        el.innerHTML = '<div class="text-center text-muted py-4">Pilih kecamatan untuk melihat rincian desa</div>';
        return;
    }
    el.innerHTML = '<div class="text-center text-muted py-2"><span class="spinner-border spinner-border-sm me-2"></span>Memuat...</div>';

    fetch('?page=dashboard&sub=monitoring&action=desa-summary&kdkec=' + encodeURIComponent(kdkec))
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.success || !res.data.length) {
                el.innerHTML = '<div class="text-center text-muted py-4">Tidak ada data desa untuk kecamatan ini</div>';
                return;
            }
            renderDesaSummary(el, res.data);
        })
        .catch(function () {
            el.innerHTML = '<div class="text-center text-danger py-4">Gagal memuat data</div>';
        });
}

function renderDesaSummary(container, data) {
    var totalDesa = data.length;
    var completedDesa = data.filter(function (d) { return parseInt(d.unassigned_sls) === 0 && parseInt(d.assigned_sls) > 0; }).length;
    var html = '<div class="mb-2 d-flex justify-content-between small fw-semibold">';
    html += '<span><i class="fas fa-flag me-1"></i>' + totalDesa + ' desa</span>';
    html += '<span class="text-success">' + completedDesa + ' selesai</span></div>';

    data.forEach(function (d) {
        var total = parseInt(d.total_sls) || 0;
        var assigned = parseInt(d.assigned_sls) || 0;
        var unassigned = parseInt(d.unassigned_sls) || 0;
        var pct = total > 0 ? Math.round((assigned / total) * 100) : 0;
        var allDone = unassigned === 0 && assigned > 0;
        var label = allDone ? 'Lengkap' : (assigned > 0 ? assigned + '/' + total : 'Kosong');
        var badgeClass = allDone ? 'badge bg-success' : (assigned > 0 ? 'badge bg-warning text-dark' : 'badge bg-secondary');

        html += '<div class="d-flex align-items-center gap-2 mb-1 py-1 border-bottom border-light">';
        html += '<div class="flex-grow-1 min-w-0">';
        html += '<div class="d-flex justify-content-between">';
        html += '<span class="text-truncate d-block" style="max-width:160px">' + escHtml(d.nmdesa) + '</span>';
        html += '<span class="' + badgeClass + '" style="font-size:9px">' + label + '</span>';
        html += '</div>';
        html += '<div class="progress" style="height:3px"><div class="progress-bar bg-' + (allDone ? 'success' : 'se2026') + '" style="width:' + pct + '%"></div></div>';
        html += '<small class="text-muted" style="font-size:9px">' + assigned + '/' + total + ' SLS · ' + (d.last_update ? d.last_update : '-') + '</small>';
        html += '</div></div>';
    });
    container.innerHTML = html;
}

// ─── SLS Assigned Tab ──────────────────────────────────────────

function loadSlsData() {
    var search = document.getElementById('slsSearchInput').value;
    var start = slsPage * MONITORING_PER_PAGE;
    var params = new URLSearchParams({
        page: 'dashboard',
        sub: 'monitoring',
        action: 'sls-data',
        draw: 1,
        start: start,
        length: MONITORING_PER_PAGE,
    });
    if (search) params.set('search[value]', search);

    var tbody = document.getElementById('slsAssignedBody');
    tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Memuat...</td></tr>';

    fetch('?' + params.toString())
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var total = res.recordsTotal || 0;
            document.getElementById('slsBadgeCount').textContent = total.toLocaleString('id-ID');
            document.getElementById('slsInfo').textContent = total.toLocaleString('id-ID') + ' baris';
            if (!res.data || !res.data.length) {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted py-4">Belum ada data SLS yang di-assign</td></tr>';
                document.getElementById('slsPrevBtn').disabled = true;
                document.getElementById('slsNextBtn').disabled = true;
                document.getElementById('slsPageInfo').textContent = 'Halaman 1';
                return;
            }
            renderSlsTable(tbody, res.data);
            updateSlsPagination(total);
        })
        .catch(function () {
            tbody.innerHTML = '<tr><td colspan="10" class="text-center text-danger py-4">Gagal memuat data</td></tr>';
        });
}

function renderSlsTable(tbody, data) {
    var html = '';
    data.forEach(function (d) {
        html += '<tr>';
        html += '<td>' + escHtml(d.nmkec) + '</td>';
        html += '<td>' + escHtml(d.nmdesa) + '</td>';
        html += '<td>' + escHtml(d.nmsls) + '</td>';
        html += '<td class="text-center">' + Number(d.kk).toLocaleString('id-ID') + '</td>';
        html += '<td class="text-center">' + Number(d.muatan).toLocaleString('id-ID') + '</td>';
        html += '<td>' + escHtml(d.pencacah) + '</td>';
        html += '<td>' + escHtml(d.pengawas) + '</td>';
        html += '<td>' + escHtml(d.task_force) + '</td>';
        html += '<td class="text-center">' + (d.status_badge || d.status) + '</td>';
        html += '<td style="font-size:10px">' + (d.tgl_assign || '-') + '</td>';
        html += '</tr>';
    });
    tbody.innerHTML = html;
}

function updateSlsPagination(total) {
    var totalPages = Math.ceil(total / MONITORING_PER_PAGE) || 1;
    document.getElementById('slsPrevBtn').disabled = slsPage <= 0;
    document.getElementById('slsNextBtn').disabled = slsPage >= totalPages - 1;
    document.getElementById('slsPageInfo').textContent = 'Halaman ' + (slsPage + 1) + ' dari ' + totalPages;
}

function slsPageChange(delta) {
    slsPage = Math.max(0, slsPage + delta);
    loadSlsData();
}

function slsSearchTimeout() {
    clearTimeout(slsSearchTimer);
    slsSearchTimer = setTimeout(function () {
        slsPage = 0;
        loadSlsData();
    }, 400);
}

// ─── Non-SLS (Prelist) Tab ─────────────────────────────────────

function loadNonSlsData() {
    var search = document.getElementById('nonslsSearchInput').value;
    var start = nonslsPage * MONITORING_PER_PAGE;
    var params = new URLSearchParams({
        page: 'dashboard',
        sub: 'monitoring',
        action: 'non-sls-data',
        draw: 1,
        start: start,
        length: MONITORING_PER_PAGE,
    });
    if (search) params.set('search[value]', search);

    var tbody = document.getElementById('nonslsBody');
    tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Memuat...</td></tr>';

    fetch('?' + params.toString())
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var total = res.recordsTotal || 0;
            document.getElementById('nonslsInfo').textContent = total.toLocaleString('id-ID') + ' baris';
            if (!res.data || !res.data.length) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data prelist</td></tr>';
                document.getElementById('nonslsPrevBtn').disabled = true;
                document.getElementById('nonslsNextBtn').disabled = true;
                document.getElementById('nonslsPageInfo').textContent = 'Halaman 1';
                return;
            }
            renderNonSlsTable(tbody, res.data);
            updateNonSlsPagination(total);
        })
        .catch(function () {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger py-4">Gagal memuat data</td></tr>';
        });
}

function renderNonSlsTable(tbody, data) {
    var html = '';
    data.forEach(function (d) {
        html += '<tr>';
        html += '<td class="text-monospace" style="font-size:10px">' + escHtml(d.idsls) + '</td>';
        html += '<td>' + escHtml(d.nm_kec) + '</td>';
        html += '<td>' + escHtml(d.nm_desa) + '</td>';
        html += '<td>' + escHtml(d.nama_sls) + '</td>';
        html += '<td class="text-center">' + Number(d.jml_kk).toLocaleString('id-ID') + '</td>';
        html += '<td class="text-center">' + Number(d.utp).toLocaleString('id-ID') + '</td>';
        html += '<td class="text-center">' + Number(d.muatan_rs).toLocaleString('id-ID') + '</td>';
        html += '<td class="text-center">' + (d.subsektor || '-') + '</td>';
        html += '<td style="font-size:10px">' + (d.imported_at || '-') + '</td>';
        html += '</tr>';
    });
    tbody.innerHTML = html;
}

function updateNonSlsPagination(total) {
    var totalPages = Math.ceil(total / MONITORING_PER_PAGE) || 1;
    document.getElementById('nonslsPrevBtn').disabled = nonslsPage <= 0;
    document.getElementById('nonslsNextBtn').disabled = nonslsPage >= totalPages - 1;
    document.getElementById('nonslsPageInfo').textContent = 'Halaman ' + (nonslsPage + 1) + ' dari ' + totalPages;
}

function nonslsPageChange(delta) {
    nonslsPage = Math.max(0, nonslsPage + delta);
    loadNonSlsData();
}

function nonslsSearchTimeout() {
    clearTimeout(nonslsSearchTimer);
    nonslsSearchTimer = setTimeout(function () {
        nonslsPage = 0;
        loadNonSlsData();
    }, 400);
}

// ─── Utility ───────────────────────────────────────────────────

function escHtml(str) {
    if (!str) return '-';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function updateClock() {
    var el = document.getElementById('clockDisplay');
    if (el) {
        var now = new Date();
        var opts = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        el.textContent = now.toLocaleDateString('id-ID', opts) + ' WIB';
    }
}

// ─── Auto-refresh ──────────────────────────────────────────────

var AUTO_REFRESH_INTERVAL = 30000;
var autoRefreshTimer = null;

function startAutoRefresh() {
    if (autoRefreshTimer) clearInterval(autoRefreshTimer);
    autoRefreshTimer = setInterval(function () {
        updateClock();
        loadKecamatanSummary();
        loadSlsData();
        loadNonSlsData();
    }, AUTO_REFRESH_INTERVAL);
}

function stopAutoRefresh() {
    if (autoRefreshTimer) {
        clearInterval(autoRefreshTimer);
        autoRefreshTimer = null;
    }
}

// ─── Init ──────────────────────────────────────────────────────

$(document).ready(function () {
    $('#tableMonitoring').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: buildDataUrl({}),
            type: 'GET',
            data: function (d) {
                var f = getFilterParams();
                Object.entries(f).forEach(function (_a) {
                    var key = _a[0], val = _a[1];
                    if (val) d[key] = val;
                });
            },
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json',
        },
        order: [[0, 'asc']],
        columns: [
            { data: 'nmkec', name: 'nmkec' },
            { data: 'nmdesa', name: 'nmdesa' },
            { data: 'nmsls', name: 'nmsls' },
            { data: 'kk', name: 'kk', className: 'text-center' },
            { data: 'usaha', name: 'usaha', className: 'text-center' },
            { data: 'muatan', name: 'muatan', className: 'text-center' },
            { data: 'pencacah', name: 'pencacah' },
            { data: 'pengawas', name: 'pengawas' },
            { data: 'task_force', name: 'task_force' },
            {
                data: 'status',
                name: 'status',
                className: 'text-center',
                render: function (data, type, row) {
                    if (type === 'display') return row.status_badge;
                    return data;
                },
            },
        ],
        drawCallback: function () {
            var api = this.api();
            var total = api.ajax.json() ? api.ajax.json().recordsFiltered : 0;
            $('#statTotalSls').text(Number(total).toLocaleString('id-ID'));
        },
        initComplete: function () {
            var searchInput = $('#tableMonitoring_filter input');
            searchInput.addClass('form-control-sm');
            searchInput.attr('placeholder', 'Cari realtime...');
            $('#tableMonitoring_filter').addClass('small').appendTo('#tableMonitoring_wrapper .row:first .col-md-6:last');
        },
    });

    // Cascade filter kecamatan to desa
    $(document).on('change', '#filterKdkec', function () {
        updateCascadeDesa();
    });

    // Load widget data
    loadKecamatanSummary();
    loadSlsData();
    loadNonSlsData();
    startAutoRefresh();
});
