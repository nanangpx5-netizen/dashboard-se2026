$(document).ready(function () {
    // Init DataTables
    if ($.fn.dataTable) {
        $('.datatable').each(function () {
            if (!$(this).hasClass('no-datatable')) {
                $(this).DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
                    },
                    pageLength: 25,
                    order: [],
                    columnDefs: [
                        { orderable: false, targets: '_all' }
                    ]
                });
            }
        });
    }

    // Auto-hide alerts after 5 seconds
    $('.alert-dismissible').each(function () {
        var alert = $(this);
        setTimeout(function () {
            alert.fadeOut(300, function () { $(this).remove(); });
        }, 5000);
    });

    // File input display
    $('input[type="file"]').on('change', function () {
        var fileName = $(this).val().split('\\').pop();
        if (fileName) {
            $(this).next('.file-name').remove();
            $(this).after('<small class="file-name text-muted d-block mt-1">' + fileName + '</small>');
        }
    });

    // ─── Sidebar Mobile Toggle ─────────────────────────────────────────
    function toggleSidebar(open) {
        var sidebar = document.getElementById('mainSidebar');
        var overlay = document.getElementById('sidebarOverlay');
        if (!sidebar || !overlay) return;
        sidebar.classList.toggle('open', open);
        overlay.classList.toggle('active', open);
        document.body.style.overflow = open ? 'hidden' : '';
    }

    document.getElementById('sidebarToggle')?.addEventListener('click', function () {
        toggleSidebar(true);
    });

    document.getElementById('sidebarCloseBtn')?.addEventListener('click', function () {
        toggleSidebar(false);
    });

    document.getElementById('sidebarOverlay')?.addEventListener('click', function () {
        toggleSidebar(false);
    });

    // Close sidebar on nav link click (mobile)
    document.querySelectorAll('.sidebar .nav-link').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth < 768) toggleSidebar(false);
        });
    });
});
