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
    var totalAssignedKec = data.filter(function (d) { return d.assigned_sls > 0; }).length;
    var html = '<div class="col-12 mb-2"><div class="badge bg-primary">Total Kecamatan Ter-assign: ' + totalAssignedKec + ' / ' + data.length + '</div></div>';
    
    data.forEach(function (d) {
        var pct = d.total_sls > 0 ? Math.round((d.assigned_sls / d.total_sls) * 100) : 0;
        var color = pct === 100 ? 'success' : (pct > 0 ? 'primary' : 'secondary');
        var activityDot = d.is_active == 1 ? '<span class="activity-dot bg-success"></span>' : '';
        
        html += '<div class="col-md-3 col-6">';
        html += '<div class="card border-0 shadow-sm mb-2 h-100 position-relative">';
        html += '<div class="card-body p-2">';
        html += '<div class="d-flex justify-content-between align-items-start">';
        html += '<h6 class="mb-0 text-truncate" title="' + d.nmkec + '" style="font-size:12px">' + d.nmkec + '</h6>';
        html += activityDot;
        html += '</div>';
        html += '<div class="d-flex align-items-center gap-2 mt-1">';
        html += '<div class="progress flex-grow-1" style="height: 4px;">';
        html += '<div class="progress-bar bg-' + color + '" style="width: ' + pct + '%"></div>';
        html += '</div>';
        html += '<small class="text-muted" style="font-size:10px">' + pct + '%</small>';
        html += '</div>';
        html += '<div class="d-flex justify-content-between mt-1" style="font-size:10px">';
        html += '<span>' + d.assigned_sls + ' / ' + d.total_sls + ' SLS</span>';
        html += '<span class="text-muted">' + (d.last_update ? d.last_update.split(' ')[0] : '-') + '</span>';
        html += '</div>';
        html += '</div></div></div>';
    });
    container.innerHTML = html;
}

// ─── Desa Summary Widget ───────────────────────────────────────

function loadDesaSummary() {
    var kdkec = document.getElementById('desaKdkecFilter').value;
    var tbody = document.getElementById('desaSummaryBody');
    if (!kdkec) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Pilih kecamatan untuk melihat rincian desa</td></tr>';
        return;
    }
    
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><span class="spinner-border spinner-border-sm"></span></td></tr>';
    
    fetch('?page=monitoring&action=desa-summary&kdkec=' + kdkec)
        .then(response => response.json())
        .then(data => {
            renderDesaSummary(tbody, data);
        });
}

function renderDesaSummary(tbody, data) {
    var html = '';
    var totalDesa = data.length;
    var totalAssignedDesa = data.filter(function (d) { return d.assigned_sls > 0; }).length;
    
    // Header info
    document.getElementById('desaSummaryTitle').innerHTML = 'Ringkasan Desa (Ter-assign: ' + totalAssignedDesa + ' / ' + totalDesa + ')';

    data.forEach(function (d) {
        var pct = d.total_sls > 0 ? Math.round((d.assigned_sls / d.total_sls) * 100) : 0;
        var color = pct === 100 ? 'success' : (pct > 0 ? 'primary' : 'secondary');
        var statusIcon = d.is_complete == 1 ? '<i class="fas fa-check-circle text-success" title="Semua SLS terdaftar"></i>' : '<i class="far fa-circle text-muted"></i>';
        
        html += '<tr>';
        html += '<td>' + d.nmdesa + '</td>';
        html += '<td class="text-center">' + d.total_sls + '</td>';
        html += '<td class="text-center">' + d.assigned_sls + '</td>';
        html += '<td class="text-center">' + d.selesai_sls + '</td>';
        html += '<td>';
        html += '<div class="progress" style="height:6px"><div class="progress-bar bg-' + color + '" style="width:' + pct + '%"></div></div>';
        html += '</td>';
        html += '<td class="text-center">' + statusIcon + '</td>';
        html += '</tr>';
    });
    tbody.innerHTML = html;
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

    var tbody = document.getElementById('slsBody');
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
        var statusBadge = d.status === 'selesai' ? 'bg-success' : (d.status === 'proses' ? 'bg-warning text-dark' : 'bg-secondary');
        html += '<tr>';
        html += '<td><small>' + escHtml(d.nmkec) + '</small><br><b>' + escHtml(d.nmdesa) + '</b></td>';
        html += '<td>' + escHtml(d.nmsls) + '</td>';
        html += '<td class="text-center">' + Number(d.kk).toLocaleString('id-ID') + '<br><small class="text-muted">KK Baru: ' + (d.jml_kk || 0) + '</small></td>';
        html += '<td class="text-center">' + Number(d.usaha || 0).toLocaleString('id-ID') + '<br><small class="text-muted">Wilker: ' + (d.usaha_wilker || 0) + '</small></td>';
        html += '<td class="text-center">' + Number(d.muatan).toLocaleString('id-ID') + '<br><small class="text-muted">Sub: ' + (d.subsektor || '-') + '</small></td>';
        html += '<td><small>PCL (Pencacah): ' + escHtml(d.pencacah) + '<br>PML (Pemeriksa): ' + escHtml(d.pengawas) + '</small></td>';
        html += '<td class="text-center"><span class="badge ' + statusBadge + '" style="font-size:9px">' + d.status + '</span></td>';
        html += '<td style="font-size:9px">' + (d.tgl_assign || '-') + '</td>';
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
        var statusBadge = d.status === 'selesai' ? 'bg-success' : (d.status === 'proses' ? 'bg-warning text-dark' : 'bg-secondary');
        html += '<tr>';
        html += '<td><small>' + d.nmkec + '</small><br><b>' + d.nmdesa + '</b></td>';
        html += '<td>' + d.nmsls + '</td>';
        html += '<td class="text-center">' + d.kk + '<br><small class="text-muted">KK Baru: ' + d.jml_kk + '</small></td>';
        html += '<td class="text-center">' + d.usaha + '<br><small class="text-muted">Wilker: ' + d.usaha_wilker + '</small></td>';
        html += '<td class="text-center">' + d.muatan + '<br><small class="text-muted">Sub: ' + d.subsektor + '</small></td>';
        html += '<td><small>PCL (Pencacah): ' + d.pencacah + '<br>PML (Pemeriksa): ' + d.pengawas + '</small></td>';
        html += '<td class="text-center"><span class="badge ' + statusBadge + '" style="font-size:9px">' + d.status + '</span></td>';
        html += '<td style="font-size:9px">' + d.tgl_assign + '</td>';
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
