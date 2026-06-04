$(document).ready(function () {
    if ($.fn.DataTable) {
        $('#tablePetugasLapangan').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
            },
            columnDefs: [
                { orderable: false, targets: [0, 7] },
                { searchable: false, targets: [0, 7] }
            ]
        });
    }
});
