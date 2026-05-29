$(document).ready(function () {
    $('#tableWilayah').DataTable({
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json',
        },
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: 0 },
        ],
        createdRow: function (row, data, dataIndex) {
            $('td:first', row).text(dataIndex + 1);
        },
    });
});
