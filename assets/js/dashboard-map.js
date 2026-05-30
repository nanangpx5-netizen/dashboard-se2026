$(document).ready(function () {
    if (typeof L === 'undefined' || typeof mapKecamatan === 'undefined') return;

    var map = L.map('mapPrelist').setView([-8.18, 113.65], 10);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>',
        maxZoom: 18,
    }).addTo(map);

    var bounds = [];
    var maxMuatan = Math.max.apply(null, mapKecamatan.map(function (d) { return d.muatan_rs; })) || 1;
    var colors = ['#198754', '#0d6efd', '#ffc107', '#dc3545'];

    mapKecamatan.forEach(function (kec) {
        getCoords(kec.nm_kec, function (lat, lng) {
            if (!lat || !lng) return;
            bounds.push([lat, lng]);

            var radius = Math.sqrt(kec.muatan_rs / maxMuatan) * 30;
            var colorIdx = kec.muatan_rs / maxMuatan > 0.5 ? 3 : kec.muatan_rs / maxMuatan > 0.25 ? 2 : kec.muatan_rs / maxMuatan > 0.1 ? 1 : 0;
            var color = colors[Math.min(colorIdx, colors.length - 1)];

            var marker = L.circleMarker([lat, lng], {
                radius: Math.max(radius, 6),
                fillColor: color,
                color: '#fff',
                weight: 1,
                opacity: 1,
                fillOpacity: 0.7,
            }).addTo(map);

            marker.bindPopup(
                '<strong>' + kec.nm_kec + '</strong><br>' +
                'KK: ' + Number(kec.jml_kk).toLocaleString('id-ID') + '<br>' +
                'UTP: ' + Number(kec.utp).toLocaleString('id-ID') + '<br>' +
                'Muatan RS: ' + Number(kec.muatan_rs).toLocaleString('id-ID') + '<br>' +
                'Beban/PPL: ' + Number(kec.beban_ppl).toLocaleString('id-ID') + '<br>' +
                'Rasio muatan/KK: ' + kec.rasio_muatan
            );
        });
    });

    setTimeout(function () {
        if (bounds.length > 1) map.fitBounds(bounds, { padding: [30, 30] });
    }, 1500);
});

function getCoords(nmKec, callback) {
    var coordMap = {
        'AJUNG':               [-8.36, 113.65],
        'AMBULU':              [-8.35, 113.60],
        'ARJASA':              [-8.10, 113.78],
        'BALUNG':              [-8.27, 113.54],
        'BANGSALSARI':         [-8.21, 113.53],
        'GUMUK MAS':           [-8.29, 113.41],
        'JELBUK':              [-8.08, 113.75],
        'JENGGAWAH':           [-8.25, 113.64],
        'JOMBANG':             [-8.23, 113.56],
        'KALISAT':             [-8.11, 113.73],
        'KALIWATES':           [-8.17, 113.68],
        'KENCONG':             [-8.12, 113.36],
        'LEDOKOMBO':           [-8.13, 113.62],
        'MAYANG':              [-8.25, 113.76],
        'MUMBULSARI':          [-8.26, 113.73],
        'PAKUSARI':            [-8.24, 113.51],
        'PANTI':               [-8.06, 113.62],
        'PATRANG':             [-8.16, 113.70],
        'PUGER':               [-8.38, 113.48],
        'RAMBIPUJI':           [-8.22, 113.60],
        'SEMBORO':             [-8.17, 113.41],
        'SILO':                [-8.26, 113.82],
        'SUKORAMBI':           [-8.16, 113.70],
        'SUKOWONO':            [-8.09, 113.77],
        'SUMBER BARU':         [-8.10, 113.70],
        'SUMBERJAMBE':         [-8.15, 113.75],
        'SUMBERSARI':          [-8.17, 113.72],
        'TANGGUL':             [-8.18, 113.53],
        'TEMPUREJO':           [-8.31, 113.79],
        'UMBULSARI':           [-8.24, 113.53],
        'WULUHAN':             [-8.18, 113.55],
    };
    var coords = coordMap[nmKec.toUpperCase()] || coordMap[nmKec.replace(/^Kec\.\s+/i, '').toUpperCase()];
    if (coords) {
        callback(coords[0], coords[1]);
    } else {
        callback(null, null);
    }
}
