$(document).ready(function () {
    $('#formImport').on('submit', function (e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);
        formData.append('ajax', '1');

        $('#importResult').hide().empty();

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function () {
                form.find('button[type="submit"]').prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm me-2"></span>Importing...');
            },
            success: function (res) {
                if (res.success) {
                    $('#importResult')
                        .html('<div class="alert alert-success">' + res.message + '</div>')
                        .show();
                    form[0].reset();
                } else {
                    $('#importResult')
                        .html('<div class="alert alert-danger">' + (res.message || 'Import gagal') + '</div>')
                        .show();
                }
            },
            error: function (xhr) {
                var msg = 'Terjadi kesalahan server';
                try {
                    var res = JSON.parse(xhr.responseText);
                    msg = res.message || msg;
                } catch (e) {}
                $('#importResult')
                    .html('<div class="alert alert-danger">' + msg + '</div>')
                    .show();
            },
            complete: function () {
                form.find('button[type="submit"]').prop('disabled', false)
                    .html('<i class="fas fa-upload me-2"></i>Import');
            }
        });
    });
});
