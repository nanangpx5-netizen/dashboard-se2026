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

// ─── Reusable UI Helpers ────────────────────────────────────────────
var UI = UI || {};

UI.showLoading = function (selector, msg) {
    msg = msg || 'Memuat...';
    var el = selector instanceof $ ? selector : $(selector);
    if (!el.length) return;
    el.each(function () {
        var $this = $(this);
        $this.css('position', 'relative');
        $this.find('.ui-loading-overlay').remove();
        $this.append('<div class="ui-loading-overlay loading-overlay"><div class="loading-spinner-sm">' + msg + '</div></div>');
    });
};

UI.hideLoading = function (selector) {
    var el = selector instanceof $ ? selector : $(selector);
    el.find('.ui-loading-overlay').addClass('fade-out');
    setTimeout(function () {
        el.find('.ui-loading-overlay').remove();
    }, 250);
};

UI.showEmpty = function (selector, icon, msg, sub) {
    icon = icon || '📋';
    msg = msg || 'Belum ada data';
    sub = sub || '';
    var el = selector instanceof $ ? selector : $(selector);
    el.each(function () {
        $(this).html(
            '<div class="empty-state">' +
            '<div class="empty-state-icon">' + icon + '</div>' +
            '<div class="empty-state-text">' + msg + '</div>' +
            (sub ? '<div class="empty-state-sub">' + sub + '</div>' : '') +
            '</div>'
        );
    });
};

UI.showLoadingRow = function (tableOrTbody, colCount, msg) {
    msg = msg || 'Memuat...';
    var $el = tableOrTbody instanceof $ ? tableOrTbody : $(tableOrTbody);
    var $tbody = $el.is('tbody') ? $el : $el.find('tbody');
    if (!$tbody.length) return;
    $tbody.html('<tr><td colspan="' + colCount + '" class="text-center text-muted py-4">' +
        '<span class="loading-spinner-sm">' + msg + '</span></td></tr>');
};

UI.showEmptyRow = function (tableOrTbody, colCount, msg) {
    msg = msg || 'Tidak ada data';
    var $el = tableOrTbody instanceof $ ? tableOrTbody : $(tableOrTbody);
    var $tbody = $el.is('tbody') ? $el : $el.find('tbody');
    if (!$tbody.length) return;
    $tbody.html('<tr><td colspan="' + colCount + '" class="text-center text-muted py-4">' + msg + '</td></tr>');
};

UI.showSkeleton = function (selector, rows, cols) {
    var el = selector instanceof $ ? selector : $(selector);
    var html = '';
    var skeletonRow = '';
    for (var c = 0; c < cols; c++) {
        skeletonRow += '<td><span class="skeleton skeleton-text"></span></td>';
    }
    for (var r = 0; r < rows; r++) {
        html += '<tr>' + skeletonRow + '</tr>';
    }
    el.html(html);
};
