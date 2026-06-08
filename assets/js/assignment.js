/**
 * Assignment Module — UI interactions
 */

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function initPetugasSelects(container) {
    container = container || document;
    var $container = $(container);
    $container.find('.petugas-select').each(function () {
        var $el = $(this);
        if ($el.data('select2')) {
            $el.select2('destroy');
        }
        $el.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: $el.find('option:first').text(),
            allowClear: true,
            dropdownParent: $el.closest('.modal')
        });

        // Trigger validation on change
        $el.on('change', function () {
            var modalId = $el.closest('.modal').attr('id');
            validateForm(modalId);
        });
    });
}

function validateForm(modalId) {
    if (modalId === 'modalAssign') {
        var sipwId = document.getElementById('assign_sipw_id').value;
        var hasPcl = $('#modalAssign select[name="pencacah_id"]').val() !== '';
        var hasPml = $('#modalAssign select[name="pengawas_id"]').val() !== '';
        var btn = document.getElementById('btnSubmitAssign');
        if (btn) btn.disabled = !(sipwId > 0 && hasPcl && hasPml);
    }
    // Tambahkan validasi untuk modal lain jika diperlukan
}

function openAssign(sipwId, nmsls, nmdesa) {
    document.getElementById('assign_sipw_id').value = sipwId;
    document.getElementById('assign_sls_info').textContent = nmsls + ' — ' + nmdesa;
    
    // Reset selects and trigger change for select2
    $('.petugas-select').val('').trigger('change');
    
    var m = document.getElementById('modalAssign');
    $('#modalAssign').one('shown.bs.modal', function () { 
        initPetugasSelects(m); 
        validateForm('modalAssign'); // Initial validation
    });
    bootstrap.Modal.getOrCreateInstance(m).show();
}

function editAssign(id, sipwId, pencacahId, pengawasId, taskForceId) {
    document.getElementById('edit_sipw_id').value = sipwId;
    document.getElementById('edit_pencacah_id').value = pencacahId !== null ? String(pencacahId) : '';
    document.getElementById('edit_pengawas_id').value = pengawasId !== null ? String(pengawasId) : '';
    document.getElementById('edit_task_force_id').value = taskForceId !== null ? String(taskForceId) : '';
    var m = document.getElementById('modalEdit');
    $('#modalEdit').one('shown.bs.modal', function () { initPetugasSelects(m); });
    bootstrap.Modal.getOrCreateInstance(m).show();
}

function filterChanged() {
    document.getElementById('filterForm').submit();
}

function loadSuggestions(kdkec, nmkec) {
    var body = document.getElementById('suggestBody');
    var badge = document.getElementById('suggestCount');
    if (!body) return;
    body.innerHTML = '<div class="text-center text-muted py-2"><i class="fas fa-spinner fa-spin me-1"></i>Memuat saran…</div>';
    badge.textContent = '…';

    var url = '?page=dashboard&sub=assignment&action=suggest&kdkec=' + encodeURIComponent(kdkec);
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.success) {
                body.innerHTML = '<div class="text-danger small">Gagal: ' + escHtml(data.message || 'unknown') + '</div>';
                return;
            }
            badge.textContent = data.total + ' petugas';
            if (data.total === 0) {
                body.innerHTML = '<div class="text-muted small">Tidak ada petugas dengan kecamatan_bertugas memuat "' + escHtml(nmkec) + '". Pilih manual dari dropdown di modal Assign.</div>';
                return;
            }
            var labels = { 
                pegawai: 'Pegawai Organik', 
                pcl: 'PCL (Petugas Pencacah Lapangan)', 
                pml: 'PML (Petugas Pemeriksa Lapangan)', 
                task_force: 'Task Force' 
            };
            var html = '<div class="row g-2">';
            ['pegawai', 'pcl', 'pml', 'task_force'].forEach(function (role) {
                var group = data.groups[role] || [];
                if (group.length === 0) return;
                var color = role === 'pegawai' ? 'success' : (role === 'pcl' ? 'primary' : (role === 'pml' ? 'warning text-dark' : 'info'));
                html += '<div class="col-md-6 col-lg-3">';
                html += '<div class="border rounded p-2 h-100">';
                html += '<div class="d-flex justify-content-between align-items-center mb-1">';
                html += '<span class="badge bg-' + color + '">' + labels[role] + '</span>';
                html += '<span class="text-muted small">' + group.length + '</span>';
                html += '</div>';
                html += '<ul class="list-unstyled small mb-0">';
                group.forEach(function (u) {
                    var loadBadge = u.current_load === 0
                        ? '<span class="badge bg-success bg-opacity-25 text-success">idle</span>'
                        : '<span class="badge bg-secondary">' + u.current_load + ' SLS</span>';
                    var posTugas = u.posisi_tugas ? '<span class="d-block text-info" style="font-size:10px">Tugas: ' + escHtml(u.posisi_tugas) + '</span>' : '';
                    html += '<li class="d-flex justify-content-between align-items-center py-1 border-bottom">';
                    html += '<span><i class="fas fa-user me-1 text-muted"></i>' + escHtml(u.nama_lengkap) + '<br><code class="small">' + escHtml(u.username) + '</code>' + posTugas + '</span>';
                    html += loadBadge;
                    html += '</li>';
                });
                html += '</ul></div></div>';
            });
            html += '</div>';
            body.innerHTML = html;
        })
        .catch(function (err) {
            body.innerHTML = '<div class="text-danger small">Error: ' + escHtml(err.message) + '</div>';
        });
}

$(document).ready(function () {
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
        var target = $(this).attr('data-bs-target');
        var tab = target === '#tabAssigned' ? 'assigned' : (target === '#tabNonSls' ? 'nonsls' : 'unassigned');
        document.getElementById('filterTab').value = tab;
    });

    var panel = document.getElementById('suggestPanel');
    if (panel) {
        loadSuggestions(panel.dataset.kdkec, panel.dataset.nmkec);
    }
});
