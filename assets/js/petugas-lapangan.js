$(document).ready(function () {
    if ($.fn.DataTable) {
        var table = $('#tablePetugasLapangan').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
            },
            columnDefs: [
                { orderable: false, targets: [0, 7] },
                { searchable: false, targets: [0, 7] }
            ]
        });

        function numberRows() {
            var api = table;
            var start = api.page.info().start;
            api.column(0, { page: 'current' }).nodes().each(function (cell, i) {
                cell.innerHTML = start + i + 1;
            });
        }

        table.on('draw.dt', numberRows);
        numberRows();
    }
});
