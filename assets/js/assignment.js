/**
 * Assignment Module — UI interactions
 */

/**
 * Assign Single — buka modal dengan info SLS
 */
function openAssign(sipwId, nmsls, nmdesa) {
    document.getElementById('assign_sipw_id').value = sipwId;
    document.getElementById('assign_sls_info').textContent = nmsls + ' — ' + nmdesa;
    document.querySelectorAll('.petugas-select').forEach(function (el) { el.value = ''; });
    new bootstrap.Modal(document.getElementById('modalAssign')).show();
}

/**
 * Edit Assign — isi modal dengan nilai existing
 */
function editAssign(id, sipwId, pencacahId, pengawasId, taskForceId) {
    document.getElementById('edit_sipw_id').value = sipwId;
    document.getElementById('edit_pencacah_id').value = pencacahId !== null ? String(pencacahId) : '';
    document.getElementById('edit_pengawas_id').value = pengawasId !== null ? String(pengawasId) : '';
    document.getElementById('edit_task_force_id').value = taskForceId !== null ? String(taskForceId) : '';
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

/**
 * Filter — submit form (resets to halaman 1)
 */
function filterChanged() {
    document.getElementById('filterForm').submit();
}

/**
 * Tab switch — update hidden tab field
 */
$(document).ready(function () {
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
        var target = $(this).attr('data-bs-target');
        var tab = target === '#tabAssigned' ? 'assigned' : 'unassigned';
        document.getElementById('filterTab').value = tab;
    });
});
