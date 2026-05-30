$(document).ready(function () {
    if (typeof Chart === 'undefined') return;

    var chartMuatan = document.getElementById('chartMuatan');
    if (chartMuatan) {
        new Chart(chartMuatan.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.muatan.labels,
                datasets: chartData.muatan.datasets,
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: {
                        ticks: { font: { size: 10 } },
                        grid: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { font: { size: 10 }, callback: function (v) { return v.toLocaleString('id-ID'); } },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                    },
                },
            },
        });
    }

    var chartKlasifikasi = document.getElementById('chartKlasifikasi');
    if (chartKlasifikasi && chartData.klasifikasi.data.some(function (v) { return v > 0; })) {
        new Chart(chartKlasifikasi.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chartData.klasifikasi.labels,
                datasets: [{
                    data: chartData.klasifikasi.data,
                    backgroundColor: chartData.klasifikasi.colors,
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 12, font: { size: 11 } },
                    },
                },
            },
        });
    }

    var chartBangunan = document.getElementById('chartBangunan');
    if (chartBangunan) {
        new Chart(chartBangunan.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.bangunan.labels,
                datasets: [{
                    label: 'Unit',
                    data: chartData.bangunan.data,
                    backgroundColor: [
                        'rgba(13, 110, 253, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(111, 66, 193, 0.7)',
                        'rgba(220, 53, 69, 0.7)',
                    ],
                    borderRadius: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: {
                        ticks: { font: { size: 11, weight: 'bold' } },
                        grid: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { font: { size: 10 }, callback: function (v) { return v.toLocaleString('id-ID'); } },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                    },
                },
            },
        });
    }

    var chartBeban = document.getElementById('chartBebanPencacah');
    if (chartBeban && chartData.bebanPencacah.labels.length) {
        new Chart(chartBeban.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chartData.bebanPencacah.labels,
                datasets: chartData.bebanPencacah.datasets,
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, padding: 8, font: { size: 10 } },
                    },
                },
                cutout: '55%',
            },
        });
    }

    if (chartData.prelist && chartData.prelist.perbandingan) {
        var elPerbandingan = document.getElementById('chartPrelistPerbandingan');
        if (elPerbandingan) {
            new Chart(elPerbandingan.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: chartData.prelist.perbandingan.labels,
                    datasets: [
                        {
                            label: 'SE2016',
                            data: chartData.prelist.perbandingan.se2016,
                            backgroundColor: 'rgba(108, 117, 125, 0.6)',
                            borderRadius: 3,
                        },
                        {
                            label: 'SE2026 (Prelist)',
                            data: chartData.prelist.perbandingan.se2026,
                            backgroundColor: 'rgba(13, 110, 253, 0.7)',
                            borderRadius: 3,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, padding: 12, font: { size: 11 } },
                        },
                    },
                    scales: {
                        x: {
                            ticks: { font: { size: 9 } },
                            grid: { display: false },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { font: { size: 10 }, callback: function (v) { return v.toLocaleString('id-ID'); } },
                            grid: { color: 'rgba(0,0,0,0.05)' },
                        },
                    },
                },
            });
        }

        var elKomposisi = document.getElementById('chartPrelistKomposisi');
        if (elKomposisi && chartData.prelist.komposisi.data.some(function (v) { return v > 0; })) {
            new Chart(elKomposisi.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: chartData.prelist.komposisi.labels,
                    datasets: [{
                        data: chartData.prelist.komposisi.data,
                        backgroundColor: chartData.prelist.komposisi.colors,
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, padding: 12, font: { size: 11 } },
                        },
                    },
                },
            });
        }
    }

    var chartProgress = document.getElementById('chartProgress');
    if (chartProgress) {
        new Chart(chartProgress.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.progress.labels,
                datasets: [
                    {
                        label: 'Proses',
                        data: chartData.progress.proses,
                        backgroundColor: 'rgba(255, 193, 7, 0.6)',
                        borderRadius: 3,
                    },
                    {
                        label: 'Selesai',
                        data: chartData.progress.selesai,
                        backgroundColor: 'rgba(25, 135, 84, 0.6)',
                        borderRadius: 3,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 12, font: { size: 11 } },
                    },
                },
                scales: {
                    x: {
                        stacked: false,
                        ticks: { font: { size: 10 } },
                        grid: { display: false },
                    },
                    y: {
                        stacked: false,
                        beginAtZero: true,
                        ticks: { font: { size: 10 }, callback: function (v) { return v.toLocaleString('id-ID'); } },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                    },
                },
            },
        });
    }
});
