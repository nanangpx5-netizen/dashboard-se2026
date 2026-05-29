<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\ReportModel;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReportController extends Controller
{
    private ReportModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new ReportModel();
    }

    public function index(): void
    {
        $action = $_GET['action'] ?? '';

        match ($action) {
            'excel'   => $this->exportExcel(),
            'csv'     => $this->exportCsv(),
            'pdf'     => $this->exportPdf(),
            'print'   => $this->printView(),
            default   => $this->showPage(),
        };
    }

    // ─── PARAMETER PAGE ──────────────────────────────────────────────────

    private function showPage(): void
    {
        $kecamatan = $this->model->kecamatanList();

        $this->data['page_title'] = 'Laporan & Ekspor';
        $this->render('report/index', [
            'kecamatan' => $kecamatan,
        ]);
    }

    // ─── BUILD DATA ──────────────────────────────────────────────────────

    private function buildData(string $jenis, ?string $kdkec = null): array
    {
        return match ($jenis) {
            'kecamatan' => $this->model->rekapKecamatan(),
            'pencacah'  => $this->model->rekapPencacah(),
            'pengawas'  => $this->model->rekapPengawas(),
            'detail'    => $this->model->rekapKecamatanFiltered($kdkec),
            'snapshot'  => [
                'summary'  => $this->model->dashboardSnapshot(),
                'exec'     => $this->model->executiveSummary(),
                'kecamatan' => $this->model->rekapKecamatan(),
            ],
            default     => [],
        };
    }

    // ─── EXCEL EXPORT ────────────────────────────────────────────────────

    private function exportExcel(): void
    {
        $jenis  = $_GET['jenis'] ?? 'kecamatan';
        $kdkec  = $_GET['kdkec'] ?? null;
        $data   = $this->buildData($jenis, $kdkec);

        $writer = new Writer();
        $writer->openToBrowser('laporan_se2026_' . $jenis . '_' . date('Ymd_His') . '.xlsx');

        $writer->addRow(Row::fromValues([strtoupper('LAPORAN DASHBOARD SE2026 — ' . $this->jenisLabel($jenis))]));
        $writer->addRow(Row::fromValues(['Dicetak: ' . date('d/m/Y H:i') . ' WIB']));
        $writer->addRow(Row::fromValues([]));

        if ($jenis === 'snapshot') {
            $this->writeSnapshotExcel($writer, $data);
        } elseif ($jenis === 'kecamatan') {
            $this->writeRekapKecExcel($writer, $data);
        } elseif ($jenis === 'pencacah' || $jenis === 'pengawas') {
            $this->writeRekapPetugasExcel($writer, $data, $jenis);
        } elseif ($jenis === 'detail') {
            $this->writeDetailExcel($writer, $data);
        }

        $writer->close();
        exit;
    }

    private function writeRekapKecExcel(Writer $w, array $data): void
    {
        $w->addRow(Row::fromValues([
            'No', 'Kecamatan', 'Total SLS', 'Assigned', 'Proses', 'Selesai',
            'Total KK', 'Total Usaha', 'Total Muatan', 'Jumlah PCL', 'Jumlah PML',
        ]));
        $no = 1;
        foreach ($data as $r) {
            $w->addRow(Row::fromValues([
                $no++, $r['kecamatan'], (int) $r['total_sls'], (int) $r['assigned'],
                (int) $r['proses'], (int) $r['selesai'], (int) $r['total_kk'],
                (int) $r['total_usaha'], (int) $r['total_muatan'],
                (int) $r['jumlah_pcl'], (int) $r['jumlah_pml'],
            ]));
        }
    }

    private function writeRekapPetugasExcel(Writer $w, array $data, string $jenis): void
    {
        $label = $jenis === 'pencacah' ? 'PCL' : 'PML';
        $w->addRow(Row::fromValues([
            'No', 'Nama ' . $label, 'Total SLS', 'Selesai', 'Proses', 'Belum',
            'Total KK', 'Total Usaha', 'Total Muatan', 'Wilayah',
        ]));
        $no = 1;
        foreach ($data as $r) {
            $w->addRow(Row::fromValues([
                $no++, $r['username'], (int) $r['total_sls'], (int) $r['selesai'],
                (int) $r['proses'], (int) $r['belum'], (int) $r['total_kk'],
                (int) $r['total_usaha'], (int) $r['total_muatan'], $r['kecamatan'],
            ]));
        }
    }

    private function writeDetailExcel(Writer $w, array $data): void
    {
        $w->addRow(Row::fromValues([
            'No', 'Kecamatan', 'Desa', 'SLS', 'Ketua', 'KK', 'Usaha', 'Muatan',
            'Pencacah', 'Pengawas', 'Status',
        ]));
        $no = 1;
        foreach ($data as $r) {
            $w->addRow(Row::fromValues([
                $no++, $r['kecamatan'], $r['desa'], $r['sls'], $r['nama_ketua'],
                (int) $r['kk'], (int) $r['usaha'], (int) $r['muatan'],
                $r['pencacah'], $r['pengawas'], $r['status'],
            ]));
        }
    }

    private function writeSnapshotExcel(Writer $w, array $data): void
    {
        $s = $data['summary'];
        $e = $data['exec'];

        $w->addRow(Row::fromValues(['RINGKASAN EKSEKUTIF']));
        $w->addRow(Row::fromValues(['Kecamatan', number_format((int) $s['total_kecamatan'])]));
        $w->addRow(Row::fromValues(['Desa', number_format((int) $s['total_desa'])]));
        $w->addRow(Row::fromValues(['Total SLS', number_format((int) $s['total_sls'])]));
        $w->addRow(Row::fromValues(['Total Muatan', number_format((int) $s['total_muatan'])]));
        $w->addRow(Row::fromValues(['Total KK', number_format((int) $s['total_kk'])]));
        $w->addRow(Row::fromValues(['Total Usaha', number_format((int) $s['total_usaha'])]));
        $w->addRow(Row::fromValues(['SLS Assigned', number_format((int) $s['assigned'])]));
        $w->addRow(Row::fromValues(['SLS Selesai', number_format((int) $s['selesai'])]));
        $w->addRow(Row::fromValues(['PCL Aktif', number_format((int) $s['total_pcl'])]));
        $w->addRow(Row::fromValues(['PML Aktif', number_format((int) $s['total_pml'])]));
        $w->addRow(Row::fromValues(['Progress', $e['total_sls'] > 0
            ? number_format($e['selesai'] / $e['total_sls'] * 100, 1) . '%'
            : '0%']));
        $w->addRow(Row::fromValues([]));

        $w->addRow(Row::fromValues(['REKAP PER KECAMATAN']));
        $this->writeRekapKecExcel($w, $data['kecamatan']);
    }

    // ─── CSV EXPORT ──────────────────────────────────────────────────────

    private function exportCsv(): void
    {
        $jenis = $_GET['jenis'] ?? 'kecamatan';
        $kdkec = $_GET['kdkec'] ?? null;
        $data  = $this->buildData($jenis, $kdkec);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="laporan_se2026_' . $jenis . '_' . date('Ymd_His') . '.csv"');
        $f = fopen('php://output', 'w');
        fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        fputcsv($f, ['LAPORAN DASHBOARD SE2026 — ' . $this->jenisLabel($jenis)]);
        fputcsv($f, ['Dicetak: ' . date('d/m/Y H:i') . ' WIB']);
        fputcsv($f, []);

        if ($jenis === 'kecamatan') {
            fputcsv($f, ['No','Kecamatan','Total SLS','Assigned','Proses','Selesai','Total KK','Total Usaha','Total Muatan','PCL','PML']);
            foreach ($data as $i => $r) {
                fputcsv($f, [$i+1, $r['kecamatan'], $r['total_sls'], $r['assigned'], $r['proses'], $r['selesai'], $r['total_kk'], $r['total_usaha'], $r['total_muatan'], $r['jumlah_pcl'], $r['jumlah_pml']]);
            }
        } elseif ($jenis === 'pencacah' || $jenis === 'pengawas') {
            $label = $jenis === 'pencacah' ? 'PCL' : 'PML';
            fputcsv($f, ['No','Nama '.$label,'Total SLS','Selesai','Proses','Belum','Total KK','Total Usaha','Total Muatan','Wilayah']);
            foreach ($data as $i => $r) {
                fputcsv($f, [$i+1, $r['username'], $r['total_sls'], $r['selesai'], $r['proses'], $r['belum'], $r['total_kk'], $r['total_usaha'], $r['total_muatan'], $r['kecamatan']]);
            }
        } elseif ($jenis === 'detail') {
            fputcsv($f, ['No','Kecamatan','Desa','SLS','Ketua','KK','Usaha','Muatan','Pencacah','Pengawas','Status']);
            foreach ($data as $i => $r) {
                fputcsv($f, [$i+1, $r['kecamatan'], $r['desa'], $r['sls'], $r['nama_ketua'], $r['kk'], $r['usaha'], $r['muatan'], $r['pencacah'], $r['pengawas'], $r['status']]);
            }
        }

        fclose($f);
        exit;
    }

    // ─── PDF EXPORT ──────────────────────────────────────────────────────

    private function exportPdf(): void
    {
        $jenis = $_GET['jenis'] ?? 'kecamatan';
        $kdkec = $_GET['kdkec'] ?? null;
        $data  = $this->buildData($jenis, $kdkec);

        $html = $this->renderPrintHtml($jenis, $data, true);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $dompdf->stream('laporan_se2026_' . $jenis . '_' . date('Ymd_His') . '.pdf', [
            'Attachment' => true,
        ]);
        exit;
    }

    // ─── PRINT VIEW ──────────────────────────────────────────────────────

    private function printView(): void
    {
        $jenis = $_GET['jenis'] ?? 'kecamatan';
        $kdkec = $_GET['kdkec'] ?? null;
        $data  = $this->buildData($jenis, $kdkec);

        $html = $this->renderPrintHtml($jenis, $data, false);

        $this->data['page_title'] = 'Cetak Laporan';
        $this->render('report/print', [
            'html'  => $html,
            'jenis' => $jenis,
            'js'    => [],
        ]);
    }

    // ─── HTML BUILDER (shared by print + PDF) ───────────────────────────

    private function renderPrintHtml(string $jenis, array $data, bool $absoluteUrl): string
    {
        $base = $absoluteUrl ? (defined('BASE_URL') ? BASE_URL : '/dashboard-se2026/') : BASE_URL;
        $title = strtoupper('LAPORAN DASHBOARD SENSUS EKONOMI 2026');
        $subtitle = 'BPS Kabupaten Jember';
        $jenisTitle = $this->jenisLabel($jenis);
        $tglCetak = date('d F Y H:i') . ' WIB';

        ob_start();
        require VIEW_PATH . '/report/print_template.php';
        return ob_get_clean();
    }

    // ─── HELPERS ─────────────────────────────────────────────────────────

    private function jenisLabel(string $jenis): string
    {
        return match ($jenis) {
            'kecamatan' => 'Rekap per Kecamatan',
            'pencacah'  => 'Rekap per Pencacah (PCL)',
            'pengawas'  => 'Rekap per Pengawas (PML)',
            'detail'    => 'Detail Wilayah',
            'snapshot'  => 'Dashboard Snapshot',
            default     => 'Laporan',
        };
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'belum'  => 'Belum',
            'proses' => 'Proses',
            'selesai' => 'Selesai',
            default  => $status,
        };
    }
}
