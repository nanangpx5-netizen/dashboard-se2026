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

    var chartProgress = document.getElementById('chartProgress');
    if (chartProgress) {
        new Chart(chartProgress.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.progress.labels,
                datasets: [
                    {
                        label: 'Assigned',
                        data: chartData.progress.assigned,
                        backgroundColor: 'rgba(13, 110, 253, 0.6)',
                        borderRadius: 3,
                    },
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
