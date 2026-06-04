/**
 * pegawai-activity.js — Charts for Rekap Aktivitas Pegawai
 * Uses data from #pegawaiActivityData JSON
 */
(function () {
    'use strict';

    const SE2026  = '#F47B20';
    const PRIMARY = '#1A73E8';
    const SUCCESS = '#198754';
    const WARNING = '#FD7E14';
    const DANGER  = '#DC3545';
    const MUTED   = '#6C757D';

    const charts = {};

    function getData() {
        const el = document.getElementById('pegawaiActivityData');
        if (!el) return null;
        try { return JSON.parse(el.textContent); } catch (e) { return null; }
    }

    function destroy(id) {
        if (charts[id]) {
            charts[id].destroy();
            delete charts[id];
        }
    }

    function renderActionType(data) {
        const ctx = document.getElementById('chartActionType');
        if (!ctx) return;
        destroy('actionType');
        if (!data.byAction || data.byAction.length === 0) return;
        charts['actionType'] = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.byAction.map(r => r.action),
                datasets: [{
                    data: data.byAction.map(r => parseInt(r.cnt, 10)),
                    backgroundColor: [SE2026, PRIMARY, SUCCESS, WARNING, DANGER, MUTED, '#6610f2', '#20c997'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 10, font: { size: 10 } } }
                }
            }
        });
    }

    function renderDaily(data) {
        const ctx = document.getElementById('chartDaily');
        if (!ctx) return;
        destroy('daily');
        const days = data.byDay || [];
        if (days.length === 0) return;
        charts['daily'] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: days.map(r => r.day),
                datasets: [{
                    label: 'Aksi',
                    data: days.map(r => parseInt(r.cnt, 10)),
                    borderColor: SE2026,
                    backgroundColor: 'rgba(244, 123, 32, 0.15)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { ticks: { maxRotation: 0, autoSkip: true } }
                }
            }
        });
    }

    function init() {
        const data = getData();
        if (!data) return;
        renderActionType(data);
        renderDaily(data);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
