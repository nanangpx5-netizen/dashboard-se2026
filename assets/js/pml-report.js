$(document).ready(function () {
    // ─── Admin/Operator: DataTable ──────────────────────────
    if ($.fn.DataTable && $('#tablePmlReport').length) {
        window.pmlTable = $('#tablePmlReport').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '?page=dashboard&sub=pml-report&action=data',
                data: function (d) {
                    d.kdkec = $('#filterKdkec').val();
                    d.status_assign = $('#filterStatus').val();
                    d.search = $('#filterSearch').val();
                }
            },
            columns: [
                {
                    data: null,
                    render: function (data, type, row, meta) {
                        return meta.row + 1 + meta.settings._iDisplayStart;
                    }
                },
                { data: 'nama_lengkap' },
                { data: 'email' },
                {
                    data: 'kecamatan_list',
                    render: function (data) { return data ? data : '-'; }
                },
                {
                    data: 'desa_list',
                    render: function (data) { return data ? data : '-'; }
                },
                { data: 'total_assigned', className: 'text-center' },
                { data: 'selesai', className: 'text-center' },
                { data: 'proses', className: 'text-center' },
                { data: 'belum', className: 'text-center' },
                {
                    data: null,
                    className: 'text-center',
                    render: function (data) {
                        var total = parseInt(data.total_assigned) || 0;
                        if (total === 0) {
                            return '<span class="badge bg-secondary">Tanpa Alokasi</span>';
                        }
                        if (data.report_id) {
                            return '<span class="badge bg-success">Sudah Lapor</span>';
                        }
                        return '<span class="badge bg-warning text-dark">Belum Lapor</span>';
                    }
                },
                {
                    data: null,
                    className: 'text-center',
                    render: function (data) {
                        var total = parseInt(data.total_assigned) || 0;
                        if (total === 0) {
                            return '<span class="text-muted small">-</span>';
                        }
                        return '<button class="btn btn-outline-primary btn-sm py-0" onclick="showDetail(' + data.id + ', \'' + data.nama_lengkap.replace(/'/g, "\\'") + '\')"><i class="fas fa-eye"></i></button>';
                    }
                }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
            },
            columnDefs: [
                { orderable: false, targets: [0, 10] },
                { searchable: false, targets: [0, 10] }
            ],
            order: [[1, 'asc']],
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            drawCallback: function () {
                var api = this.api();
                api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                    cell.innerHTML = api.page.info().start + i + 1;
                });
            }
        });
    }

    window.reloadTable = function () {
        if (window.pmlTable) {
            window.pmlTable.ajax.reload();
        }
    };

    window.reloadStats = function () {
        var kdkec = $('#filterKdkec').val();
        var periode = $('#filterPeriode').val();
        $.getJSON('?page=dashboard&sub=pml-report&action=stats', { kdkec: kdkec, periode: periode }, function (res) {
            if (res && res.total_pml !== undefined) {
                $('.text-primary.fs-4').text(res.total_pml.toLocaleString());
                $('.text-success.fs-4').first().text(res.with_assignment.toLocaleString());
                $('.text-warning.fs-4').html(res.selesai.toLocaleString() + '<small class=\"fs-6\">/' + res.total_sls.toLocaleString() + '</small>');
                $('.text-secondary.fs-4').text(res.without_assignment.toLocaleString());
            }
        });
        if (window.pmlTable) {
            window.pmlTable.ajax.reload();
        }
    };

    window.showDetail = function (pmlId, pmlName) {
        $('#detailPmlName').text(pmlName);
        $('#detailBody').html('<tr><td colspan="6" class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin me-1"></i>Memuat...</td></tr>');
        $('#modalDetail').modal('show');

        $.getJSON('?page=dashboard&sub=pml-report&action=detail', { pml_id: pmlId, kdkec: $('#filterKdkec').val() }, function (res) {
            if (res.success && res.data.length) {
                var html = '';
                $.each(res.data, function (i, r) {
                    var badge = 'bg-secondary';
                    var label = 'Belum';
                    if (r.status === 'selesai') { badge = 'bg-success'; label = 'Selesai'; }
                    else if (r.status === 'proses') { badge = 'bg-warning text-dark'; label = 'Proses'; }
                    html += '<tr><td>' + escapeHtml(r.nmsls) + '</td><td>' + escapeHtml(r.nmdesa) + '</td><td>' + escapeHtml(r.nmkec) + '</td><td><span class="badge ' + badge + '">' + label + '</span></td><td>' + (r.tanggal_mulai || '-') + '</td><td>' + (r.tanggal_selesai || '-') + '</td></tr>';
                });
                $('#detailBody').html(html);
            } else {
                $('#detailBody').html('<tr><td colspan="6" class="text-center text-muted py-3">Tidak ada data SLS</td></tr>');
            }
        }).fail(function () {
            $('#detailBody').html('<tr><td colspan="6" class="text-center text-danger py-3">Gagal memuat data</td></tr>');
        });
    };

    window.resetFilters = function () {
        $('#filterKdkec').val('');
        $('#filterStatus').val('');
        $('#filterSearch').val('');
        reloadStats();
    };

    // ─── PML: Form Submit ─────────────────────────────────
    if ($('#formPmlReport').length) {
        $('#formPmlReport').on('submit', function (e) {
            e.preventDefault();
            var form = $(this);
            var btnSubmit = $('#btnSubmitReport');
            var btnLoading = $('#btnLoadingReport');
            var alertBox = $('#pmlReportAlert');

            alertBox.addClass('d-none').removeClass('alert-success alert-danger');
            btnSubmit.hide();
            btnLoading.show();

            $.ajax({
                url: '?page=dashboard&sub=pml-report&action=submit',
                type: 'POST',
                dataType: 'json',
                data: {
                    periode: $('#inputPeriode').val(),
                    catatan: $('#inputCatatan').val()
                },
                success: function (res) {
                    if (res.success) {
                        alertBox.removeClass('d-none alert-danger').addClass('alert-success').html('<i class="fas fa-check-circle me-1"></i>' + (res.message || 'Laporan berhasil dikirim.'));
                        btnSubmit.replaceWith('<div class="text-success fw-semibold small py-1"><i class="fas fa-check-circle me-1"></i>Laporan sudah dikirim.</div>');
                    } else {
                        alertBox.removeClass('d-none alert-success').addClass('alert-danger').html('<i class="fas fa-exclamation-circle me-1"></i>' + (res.message || 'Gagal mengirim laporan.'));
                        btnSubmit.show();
                    }
                },
                error: function (xhr) {
                    var msg = 'Terjadi kesalahan server.';
                    try {
                        var r = JSON.parse(xhr.responseText);
                        if (r.message) msg = r.message;
                    } catch (e) {}
                    alertBox.removeClass('d-none alert-success').addClass('alert-danger').html('<i class="fas fa-exclamation-circle me-1"></i>' + msg);
                    btnSubmit.show();
                },
                complete: function () {
                    btnLoading.hide();
                }
            });
        });
    }
});

var searchTimer;
function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function () {
        reloadTable();
    }, 400);
}

function escapeHtml(str) {
    if (!str) return '-';
    return $('<span>').text(str).html();
}
