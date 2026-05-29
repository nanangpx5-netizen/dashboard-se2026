/**
 * Audit Log — DataTables server-side + filter controls
 */
function getFilterParams() {
    return {
        module: document.getElementById('filterModule').value,
        user_id: document.getElementById('filterUser').value,
        date_from: document.getElementById('filterDateFrom').value,
        date_to: document.getElementById('filterDateTo').value,
    };
}

function buildDataUrl(filters) {
    var params = new URLSearchParams({ page: 'dashboard', sub: 'audit', action: 'data' });
    Object.entries(filters).forEach(function (e) {
        if (e[1]) params.set(e[0], e[1]);
    });
    return '?' + params.toString();
}

function reloadTable() {
    var dt = $('#tableAuditLog').DataTable();
    var f = getFilterParams();
    dt.ajax.url(buildDataUrl(f));
    dt.ajax.reload();
}

function resetFilters() {
    document.getElementById('filterModule').value = '';
    document.getElementById('filterUser').value = '';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    reloadTable();
}

function showDetail(html) {
    var decoded = decodeURIComponent(html);
    var modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = '<div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">' +
        '<div class="modal-header py-2"><h6 class="modal-title fw-semibold"><i class="fas fa-info-circle me-1"></i>Detail Audit</h6>' +
        '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
        '<div class="modal-body p-2" style="overflow-x:auto">' + decoded + '</div>' +
        '<div class="modal-footer py-1"><button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button></div></div></div>';
    document.body.appendChild(modal);
    var m = new bootstrap.Modal(modal);
    modal.addEventListener('hidden.bs.modal', function () { modal.remove(); });
    m.show();
}

$(document).ready(function () {
    $('#tableAuditLog').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: buildDataUrl({}),
            type: 'GET',
            data: function (d) {
                var f = getFilterParams();
                Object.entries(f).forEach(function (e) {
                    if (e[1]) d[e[0]] = e[1];
                });
            },
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[0, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json',
        },
        columns: [
            {
                data: 'created_at',
                render: function (d) {
                    if (!d) return '-';
                    var t = d.replace(' ', 'T');
                    return '<small class="text-muted" title="' + d + '">' + d + '</small>';
                },
            },
            { data: 'username', defaultContent: '-' },
            {
                data: 'action_label',
                render: function (d, type, row) {
                    var badge = 'bg-secondary';
                    if (row.action === 'login' || row.action === 'logout') badge = 'bg-info';
                    else if (row.action.indexOf('import_success') >= 0 || row.action.indexOf('import_complete') >= 0) badge = 'bg-success';
                    else if (row.action.indexOf('failed') >= 0 || row.action.indexOf('gagal') >= 0) badge = 'bg-danger';
                    else if (row.action.indexOf('create') >= 0 || row.action.indexOf('insert') >= 0) badge = 'bg-primary';
                    else if (row.action.indexOf('delete') >= 0 || row.action.indexOf('cancelled') >= 0) badge = 'bg-warning text-dark';
                    return '<span class="badge ' + badge + ' rounded-pill">' + d + '</span>';
                },
            },
            {
                data: 'module',
                render: function (d) { return '<span class="text-uppercase small fw-semibold">' + (d || '-') + '</span>'; },
            },
            {
                data: 'description',
                render: function (d) {
                    if (!d || d === 'null' || d === '') return '-';
                    if (d.length > 80) return d.substring(0, 80) + '...';
                    return d;
                },
            },
            {
                data: null,
                className: 'text-center',
                orderable: false,
                render: function (d) {
                    if (!d.detail_html || d.detail_html === '') {
                        return '<span class="text-muted small">-</span>';
                    }
                    return '<button class="btn btn-sm btn-outline-info py-0" onclick="showDetail(\'' + encodeURIComponent(d.detail_html) + '\')"><i class="fas fa-eye"></i></button>';
                },
            },
        ],
        initComplete: function () {
            var searchInput = $('#tableAuditLog_filter input');
            searchInput.addClass('form-control-sm');
            searchInput.attr('placeholder', 'Cari aktivitas...');
            $('#tableAuditLog_filter').addClass('small');
        },
    });
});
