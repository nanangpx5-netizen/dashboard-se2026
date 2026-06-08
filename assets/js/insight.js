/**
 * insight.js — Chart.js widgets for Insight & Analisa page
 * Uses window.INSIGHT_DATA populated from view
 */
(function () {
    'use strict';

    const SE2026 = '#F47B20';
    const PRIMARY = '#1A73E8';
    const SUCCESS = '#198754';
    const WARNING = '#FD7E14';
    const DANGER  = '#DC3545';
    const INFO    = '#0DCAF0';
    const MUTED   = '#6C757D';

    const data = window.INSIGHT_DATA || {};

    const charts = {};

    function destroy(id) {
        if (charts[id]) {
            charts[id].destroy();
            delete charts[id];
        }
    }

    function renderAnomaliKec() {
        const ctx = document.getElementById('chartAnomaliKec');
        if (!ctx) return;
        destroy('anomali');
        const rows = (data.anomali || []).slice(0, 20);
        charts.anomali = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: rows.map(r => r.nmkec),
                datasets: [
                    { label: 'Muatan = 0', data: rows.map(r => +r.mu_zero), backgroundColor: DANGER },
                    { label: 'KK = 0',     data: rows.map(r => +r.kk_zero),  backgroundColor: WARNING },
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    x: { stacked: true, ticks: { font: { size: 9 }, maxRotation: 60 } },
                    y: { stacked: true, beginAtZero: true }
                }
            }
        });
    }

    function renderDistribusi() {
        const ctx = document.getElementById('chartDistribusi');
        if (!ctx) return;
        destroy('distribusi');
        const rows = data.distribusi || [];
        const colors = [DANGER, '#FF6B6B', WARNING, '#FFC107', SE2026, PRIMARY, SUCCESS, MUTED];
        charts.distribusi = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: rows.map(r => `${r.bin} (${r.sls})`),
                datasets: [{
                    data: rows.map(r => +r.sls),
                    backgroundColor: colors.slice(0, rows.length),
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 12, font: { size: 10 } } },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const r = rows[ctx.dataIndex];
                                return `${r.bin}: ${r.sls} SLS (${r.pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    function renderBebanKerja() {
        const ctx = document.getElementById('chartBebanKerja');
        if (!ctx) return;
        destroy('beban');
        const rows = data.beban || [];
        const colors = rows.map(r => {
            if (r.kategori_beban === 'RINGAN') return SUCCESS;
            if (r.kategori_beban === 'SEDANG') return WARNING;
            return DANGER;
        });
        charts.beban = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: rows.map(r => r.nmkec),
                datasets: [{
                    label: 'Avg Muatan',
                    data: rows.map(r => +r.avg_mu),
                    backgroundColor: colors,
                    borderRadius: 4
                }, {
                    label: 'Std Dev',
                    data: rows.map(r => +r.std_mu),
                    backgroundColor: 'rgba(108, 117, 125, 0.3)',
                    type: 'line',
                    borderColor: MUTED,
                    borderWidth: 2,
                    fill: false,
                    tension: 0.3,
                    pointRadius: 2
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    x: { ticks: { font: { size: 9 }, maxRotation: 60 } },
                    y: { beginAtZero: true, title: { display: true, text: 'Avg Muatan', font: { size: 10 } } }
                }
            }
        });
    }

    function renderUserPool() {
        const ctx = document.getElementById('chartUserPool');
        if (!ctx) return;
        destroy('userPool');
        const pool = { pcl: { a: 0, i: 0 }, pml: { a: 0, i: 0 }, task_force: { a: 0, i: 0 } };
        (data.userPool || []).forEach(u => {
            if (pool[u.role]) {
                pool[u.role][u.status_akun === 'active' ? 'a' : 'i'] = +u.jumlah;
            }
        });
        charts.userPool = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['PCL', 'PML', 'Task Force'],
                datasets: [
                    { label: 'Aktif',     data: [pool.pcl.a, pool.pml.a, pool.task_force.a], backgroundColor: SUCCESS },
                    { label: 'Non-aktif', data: [pool.pcl.i, pool.pml.i, pool.task_force.i], backgroundColor: MUTED }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } },
                scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }
            }
        });
    }

    function renderCoverage() {
        const ctx = document.getElementById('chartCoverage');
        if (!ctx) return;
        destroy('coverage');
        const rows = data.coverage || [];
        charts.coverage = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: rows.map(r => r.nm_kec),
                datasets: [
                    { label: 'Prelist (muatan_rs)', data: rows.map(r => +r.prelist_muatan), backgroundColor: PRIMARY, borderRadius: 4 },
                    { label: 'SIPW actual',         data: rows.map(r => +r.sipw_muatan),     backgroundColor: SE2026, borderRadius: 4 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } },
                scales: {
                    x: { ticks: { font: { size: 9 }, maxRotation: 60 } },
                    y: { beginAtZero: true }
                }
            }
        });
    }

    function loadAnomaliDetail(type) {
        const tbody = document.querySelector('#tblAnomali tbody');
        if (!tbody) return;
        UI.showLoadingRow(tbody, 6, 'Memuat...');
        fetch(`?page=dashboard&sub=insight&action=anomali-detail&type=${type}&limit=100`, { credentials: 'same-origin' })
            .then(r => r.json())
            .then(json => {
                if (!json.success || !json.data || json.data.length === 0) {
                    UI.showEmptyRow(tbody, 6, 'Tidak ada data');
                    return;
                }
                const max = Math.max(...json.data.map(r => +r.muatan || 0));
                tbody.innerHTML = json.data.map(r => {
                    const muColor = r.muatan == 0 ? 'text-danger fw-bold' : (r.muatan > 200 ? 'text-warning fw-bold' : '');
                    return `<tr>
                        <td>${escapeHtml(r.kdkec)}</td>
                        <td>${escapeHtml((r.nmsls || r.idsubsls || '').substring(0, 30))}</td>
                        <td class="text-end">${(+r.kk || 0).toLocaleString('id')}</td>
                        <td class="text-end ${muColor}">${(+r.muatan || 0).toLocaleString('id')}</td>
                        <td class="text-end">${(+r.btt || 0).toLocaleString('id')}</td>
                        <td class="text-end">${(+r.usaha || 0).toLocaleString('id')}</td>
                    </tr>`;
                }).join('');
            })
            .catch(err => {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-3">Error: ${escapeHtml(err.message)}</td></tr>`;
            });
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function renderDeltaSls() {
        const ctx = document.getElementById('chartDeltaSls');
        if (!ctx) return;
        const rows = data.deltaDetail || [];
        if (rows.length === 0) return;
        destroy('deltaSls');
        charts.deltaSls = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: rows.map(r => r.nmkec),
                datasets: [
                    { label: 'SLS Unik', data: rows.map(r => +r.sls_unik || 0), backgroundColor: PRIMARY },
                    { label: 'Sub-SLS Tambahan', data: rows.map(r => +r.sls_extra || 0), backgroundColor: SE2026 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, font: { size: 10 } } },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    x: { stacked: true, beginAtZero: true, title: { display: true, text: 'Jumlah SLS', font: { size: 10 } } },
                    y: { stacked: true, ticks: { font: { size: 9 } } }
                }
            }
        });
    }

    function initDeltaEvents() {
        const filter = document.getElementById('filterDeltaKec');
        if (filter) {
            filter.addEventListener('change', function () {
                const v = this.value;
                document.querySelectorAll('.delta-sls-row').forEach(function (tr) {
                    const show = !v || tr.dataset.kdkec === v;
                    tr.style.display = show ? '' : 'none';
                    if (!show) {
                        const detail = document.querySelector('.sub-detail-row[data-idsls="' + tr.dataset.idsls + '"]');
                        if (detail) { detail.classList.add('d-none'); }
                    }
                });
                var count = document.querySelectorAll('.delta-sls-row:not([style*="none"])').length;
                var el = document.getElementById('deltaSlsCount');
                if (el) el.textContent = count + ' SLS';
            });
        }
        document.querySelectorAll('.toggle-sub-detail').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var idsls = this.dataset.idsls;
                var tr = document.querySelector('.sub-detail-row[data-idsls="' + idsls + '"]');
                if (!tr) return;
                var wasHidden = tr.classList.contains('d-none');
                tr.classList.toggle('d-none');
                var icon = this.querySelector('i');
                if (icon) icon.className = wasHidden ? 'fas fa-chevron-up' : 'fas fa-chevron-down';
                if (wasHidden && !tr.dataset.loaded) {
                    tr.dataset.loaded = '1';
                    var tbody = tr.querySelector('tbody');
                    if (tbody) {
                        fetch('?page=dashboard&sub=insight&action=sub-sls-detail&idsls=' + idsls, { credentials: 'same-origin' })
                            .then(function (r) { return r.json(); })
                            .then(function (json) {
                                if (!json.success || !json.data) {
                                    tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-2">Gagal memuat data</td></tr>';
                                    return;
                                }
                                tbody.innerHTML = json.data.map(function (r) {
                                    var cls = (r.flag_kk_zero || r.flag_identical) ? ' class="table-danger"' : '';
                                    var badge = '';
                                    if (r.flag_kk_zero && r.flag_identical) {
                                        badge = '<span class="badge bg-danger">KK=0 & Identik</span>';
                                    } else if (r.flag_kk_zero) {
                                        badge = '<span class="badge bg-warning text-dark">KK=0</span>';
                                    } else if (r.flag_identical) {
                                        badge = '<span class="badge bg-warning text-dark">Identik</span>';
                                    } else {
                                        badge = '<span class="badge bg-success">OK</span>';
                                    }
                                    return '<tr' + cls + '>' +
                                        '<td>' + r.sub_ke + '</td>' +
                                        '<td><code>' + escapeHtml(r.idsubsls) + '</code></td>' +
                                        '<td>' + escapeHtml(String(r.id)) + '</td>' +
                                        '<td class="text-end">' + (+r.kk).toLocaleString('id') + '</td>' +
                                        '<td class="text-end">' + (+r.muatan).toLocaleString('id') + '</td>' +
                                        '<td class="text-end">' + (+r.btt).toLocaleString('id') + '</td>' +
                                        '<td class="text-end">' + (+r.bku).toLocaleString('id') + '</td>' +
                                        '<td class="text-end">' + (+r.usaha).toLocaleString('id') + '</td>' +
                                        '<td class="text-center">' + badge + '</td>' +
                                        '</tr>';
                                }).join('');
                            })
                            .catch(function () {
                                tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger py-2">Error memuat data</td></tr>';
                            });
                    }
                }
            });
        });
    }

    function init() {
        renderAnomaliKec();
        renderDistribusi();
        renderBebanKerja();
        renderUserPool();
        renderCoverage();
        renderDeltaSls();
        initDeltaEvents();
        loadAnomaliDetail('muatan_zero');

        const sel = document.getElementById('anomaliType');
        if (sel) sel.addEventListener('change', e => loadAnomaliDetail(e.target.value));

        const search = document.getElementById('searchBeban');
        if (search) {
            search.addEventListener('input', debounce(e => {
                const q = e.target.value.toLowerCase();
                document.querySelectorAll('tr[data-kec]').forEach(tr => {
                    tr.style.display = tr.dataset.kec.toLowerCase().includes(q) ? '' : 'none';
                });
            }, 250));
        }
    }

    function debounce(fn, ms) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), ms);
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
