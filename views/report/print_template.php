<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= e($title) ?> — <?= e($jenisTitle) ?></title>
    <style>
        @page { margin: 20mm 15mm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10pt; color: #222; line-height: 1.4; }
        .header { text-align: center; border-bottom: 2px solid #1a3a5c; padding-bottom: 10px; margin-bottom: 15px; }
        .header h1 { font-size: 14pt; margin: 0; color: #1a3a5c; letter-spacing: 1px; }
        .header h2 { font-size: 11pt; margin: 2px 0; font-weight: normal; color: #444; }
        .header .meta { font-size: 8pt; color: #888; margin-top: 5px; }
        .section-title { font-size: 11pt; font-weight: bold; color: #1a3a5c; margin: 15px 0 8px 0; padding-bottom: 3px; border-bottom: 1px solid #ccc; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 8.5pt; }
        th { background: #1a3a5c; color: #fff; padding: 5px 4px; text-align: center; font-weight: bold; font-size: 8pt; }
        td { padding: 4px; border: 1px solid #ccc; vertical-align: top; }
        tr:nth-child(even) { background: #f5f7fa; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .text-nowrap { white-space: nowrap; }

        .summary-cards { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 15px; }
        .summary-card { flex: 1; min-width: 100px; border: 1px solid #ccc; border-radius: 4px; padding: 8px; text-align: center; background: #f9fafb; }
        .summary-card .label { font-size: 7pt; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
        .summary-card .value { font-size: 14pt; font-weight: bold; color: #1a3a5c; margin-top: 2px; }

        .footer { margin-top: 30px; text-align: center; font-size: 8pt; color: #999; border-top: 1px solid #ddd; padding-top: 8px; }
        .signature { margin-top: 30px; display: flex; justify-content: space-between; }
        .signature div { text-align: center; width: 45%; }
        .signature .space { height: 60px; }

        @media print {
            @page { margin: 15mm 10mm; }
            body { font-size: 9pt; }
            table { font-size: 7.5pt; }
            th { font-size: 7pt; padding: 3px 3px; }
            td { padding: 3px; }
            .summary-card .value { font-size: 12pt; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="header">
    <h1><?= e($title) ?></h1>
    <h2><?= e($subtitle) ?></h2>
    <div class="meta"><?= e($jenisTitle) ?> — Dicetak: <?= e($tglCetak) ?></div>
</div>

<?php if ($jenis === 'snapshot'): ?>
    <?php $s = $data['summary']; $e = $data['exec']; ?>
    <div class="summary-cards">
        <div class="summary-card"><div class="label">Kecamatan</div><div class="value"><?= number_format((int) $s['total_kecamatan']) ?></div></div>
        <div class="summary-card"><div class="label">Desa</div><div class="value"><?= number_format((int) $s['total_desa']) ?></div></div>
        <div class="summary-card"><div class="label">Total SLS</div><div class="value"><?= number_format((int) $s['total_sls']) ?></div></div>
        <div class="summary-card"><div class="label">Total Muatan</div><div class="value"><?= number_format((int) $s['total_muatan']) ?></div></div>
        <div class="summary-card"><div class="label">Total KK</div><div class="value"><?= number_format((int) $s['total_kk']) ?></div></div>
        <div class="summary-card"><div class="label">Total Usaha</div><div class="value"><?= number_format((int) $s['total_usaha']) ?></div></div>
        <div class="summary-card"><div class="label">Assigned</div><div class="value"><?= number_format((int) $s['assigned']) ?></div></div>
        <div class="summary-card"><div class="label">Selesai</div><div class="value"><?= number_format((int) $s['selesai']) ?></div></div>
        <div class="summary-card"><div class="label">Progress</div><div class="value"><?= $e['total_sls'] > 0 ? number_format($e['selesai'] / $e['total_sls'] * 100, 1) . '%' : '0%' ?></div></div>
        <div class="summary-card"><div class="label">PCL Aktif</div><div class="value"><?= number_format((int) $s['total_pcl']) ?></div></div>
        <div class="summary-card"><div class="label">PML Aktif</div><div class="value"><?= number_format((int) $s['total_pml']) ?></div></div>
        <div class="summary-card"><div class="label">Task Force</div><div class="value"><?= number_format((int) $s['total_tf']) ?></div></div>
    </div>

    <div class="section-title">Rekap per Kecamatan</div>
    <table>
        <thead>
            <tr>
                <th>No</th><th>Kecamatan</th><th>Total SLS</th><th>Assigned</th><th>Proses</th><th>Selesai</th>
                <th>KK</th><th>Usaha</th><th>Muatan</th><th>PCL</th><th>PML</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($data['kecamatan'] as $r): ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($r['kecamatan']) ?></td>
                <td class="text-end"><?= number_format((int) $r['total_sls']) ?></td>
                <td class="text-end"><?= number_format((int) $r['assigned']) ?></td>
                <td class="text-end"><?= number_format((int) $r['proses']) ?></td>
                <td class="text-end"><?= number_format((int) $r['selesai']) ?></td>
                <td class="text-end"><?= number_format((int) $r['total_kk']) ?></td>
                <td class="text-end"><?= number_format((int) $r['total_usaha']) ?></td>
                <td class="text-end"><?= number_format((int) $r['total_muatan']) ?></td>
                <td class="text-end"><?= number_format((int) $r['jumlah_pcl']) ?></td>
                <td class="text-end"><?= number_format((int) $r['jumlah_pml']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php elseif ($jenis === 'kecamatan'): ?>
    <table>
        <thead>
            <tr>
                <th>No</th><th>Kecamatan</th><th>Total SLS</th><th>Assigned</th><th>Proses</th><th>Selesai</th>
                <th>KK</th><th>Usaha</th><th>Muatan</th><th>PCL</th><th>PML</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($data as $r): ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($r['kecamatan']) ?></td>
                <td class="text-end"><?= number_format((int) $r['total_sls']) ?></td>
                <td class="text-end"><?= number_format((int) $r['assigned']) ?></td>
                <td class="text-end"><?= number_format((int) $r['proses']) ?></td>
                <td class="text-end"><?= number_format((int) $r['selesai']) ?></td>
                <td class="text-end"><?= number_format((int) $r['total_kk']) ?></td>
                <td class="text-end"><?= number_format((int) $r['total_usaha']) ?></td>
                <td class="text-end"><?= number_format((int) $r['total_muatan']) ?></td>
                <td class="text-end"><?= number_format((int) $r['jumlah_pcl']) ?></td>
                <td class="text-end"><?= number_format((int) $r['jumlah_pml']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php elseif ($jenis === 'pencacah' || $jenis === 'pengawas'): ?>
    <?php $label = $jenis === 'pencacah' ? 'PCL' : 'PML'; ?>
    <table>
        <thead>
            <tr>
                <th>No</th><th>Nama <?= e($label) ?></th><th>Total SLS</th><th>Selesai</th><th>Proses</th><th>Belum</th>
                <th>KK</th><th>Usaha</th><th>Muatan</th><th>Wilayah</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($data as $r): ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($r['username']) ?></td>
                <td class="text-end"><?= number_format((int) $r['total_sls']) ?></td>
                <td class="text-end"><?= number_format((int) $r['selesai']) ?></td>
                <td class="text-end"><?= number_format((int) $r['proses']) ?></td>
                <td class="text-end"><?= number_format((int) $r['belum']) ?></td>
                <td class="text-end"><?= number_format((int) $r['total_kk']) ?></td>
                <td class="text-end"><?= number_format((int) $r['total_usaha']) ?></td>
                <td class="text-end"><?= number_format((int) $r['total_muatan']) ?></td>
                <td><?= htmlspecialchars($r['kecamatan']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php elseif ($jenis === 'detail'): ?>
    <table>
        <thead>
            <tr>
                <th>No</th><th>Kecamatan</th><th>Desa</th><th>SLS</th><th>Ketua</th>
                <th>KK</th><th>Usaha</th><th>Muatan</th><th>Pencacah</th><th>Pengawas</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($data as $r): ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($r['kecamatan']) ?></td>
                <td><?= htmlspecialchars($r['desa']) ?></td>
                <td><?= htmlspecialchars($r['sls']) ?></td>
                <td><?= htmlspecialchars($r['nama_ketua']) ?></td>
                <td class="text-end"><?= number_format((int) $r['kk']) ?></td>
                <td class="text-end"><?= number_format((int) $r['usaha']) ?></td>
                <td class="text-end"><?= number_format((int) $r['muatan']) ?></td>
                <td><?= htmlspecialchars($r['pencacah']) ?></td>
                <td><?= htmlspecialchars($r['pengawas']) ?></td>
                <td><?= \App\Controllers\ReportController::statusLabel($r['status']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<div class="signature">
    <div>
        <div>Mengetahui,<br>Kepala BPS Kabupaten Jember</div>
        <div class="space"></div>
        <div>(_________________________)</div>
        <div>NIP. </div>
    </div>
    <div>
        <div>Jember, <?= date('d F Y') ?><br>Koordinator Teknis</div>
        <div class="space"></div>
        <div>(_________________________)</div>
        <div>NIP. </div>
    </div>
</div>

<div class="footer">
    Laporan ini dihasilkan secara otomatis dari Dashboard SE2026 BPS Jember — <?= e($tglCetak) ?>
</div>

</body>
</html>
