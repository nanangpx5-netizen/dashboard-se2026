/**
 * Monitoring Wilayah — DataTables server-side + cascade filter + export
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

    // Cascade filter kecamatan → desa
    $(document).on('change', '#filterKdkec', function () {
        updateCascadeDesa();
    });
});
