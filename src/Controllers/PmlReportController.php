<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Helpers\AuditLog;
use App\Helpers\Session;
use App\Models\PmlReportModel;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

class PmlReportController extends Controller
{
    private PmlReportModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new PmlReportModel();
    }

    public function index(): void
    {
        $action = $_GET['action'] ?? '';

        match ($action) {
            'data'     => $this->dataTable(),
            'stats'    => $this->stats(),
            'detail'   => $this->detail(),
            'submit'   => $this->handleSubmit(),
            'export'   => $this->exportExcel(),
            default    => $this->showPage(),
        };
    }

    private function showPage(): void
    {
        $periode = $_GET['periode'] ?? date('Y-m');
        $stats   = $this->model->getStats($periode);
        $kecamatan = [];

        try {
            $pdo = Database::instance()->pdo();
            $kecamatan = $pdo->query("SELECT DISTINCT si.kdkec, si.nmkec FROM sipw_import si ORDER BY si.nmkec")->fetchAll();
        } catch (\Throwable $e) {
            $kecamatan = [];
        }

        $this->data['page_title'] = 'Laporan Statistik PML SLS';
        $this->render('pml-report/index', [
            'stats'     => $stats,
            'kecamatan' => $kecamatan,
            'periode'   => $periode,
            'js'        => ['pml-report'],
        ]);
    }

    private function dataTable(): void
    {
        $filters = [
            'kdkec'        => $_GET['kdkec'] ?? '',
            'status_assign' => $_GET['status_assign'] ?? '',
            'search'       => $_GET['search'] ?? '',
        ];
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($_GET['per_page'] ?? 25)));

        $rows  = $this->model->getReports($filters, $page, $perPage);
        $total = $this->model->countReports($filters);

        $this->json([
            'draw'            => (int) ($_GET['draw'] ?? 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $rows,
        ]);
    }

    private function stats(): void
    {
        $periode = $_GET['periode'] ?? date('Y-m');
        $kdkec   = $_GET['kdkec'] ?? '';
        $this->json($this->model->getStats($periode, $kdkec ?: null));
    }

    private function detail(): void
    {
        $pmlId = (int) ($_GET['pml_id'] ?? 0);
        $kdkec = $_GET['kdkec'] ?? '';
        if (!$pmlId) {
            $this->json(['error' => true, 'message' => 'ID PML tidak valid']);
            return;
        }
        $detail = $this->model->getDetail($pmlId, $kdkec ?: null);
        $this->json(['success' => true, 'data' => $detail]);
    }

    private function handleSubmit(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => true, 'message' => 'Method not allowed']);
            return;
        }

        $user = Session::get('user');
        if (!$user || $user['role'] !== 'pml') {
            $this->json(['error' => true, 'message' => 'Hanya PML yang dapat mengirim laporan']);
            return;
        }

        $pmlId    = (int) $user['id'];
        $periode  = $_POST['periode'] ?? date('Y-m');
        $catatan  = $_POST['catatan'] ?? '';

        // Validasi format periode
        if (!preg_match('/^\d{4}-\d{2}$/', $periode)) {
            $this->json(['error' => true, 'message' => 'Format periode tidak valid (YYYY-MM)']);
            return;
        }

        // Validasi: PML harus memiliki alokasi SLS
        $totalAssign = $this->model->countPmlAssignments($pmlId);
        if ($totalAssign === 0) {
            $this->json(['error' => true, 'message' => 'Anda belum memiliki alokasi SLS. Hubungi admin untuk penugasan.']);
            return;
        }

        // Cek duplikasi
        $existing = $this->model->getReportByPmlPeriode($pmlId, $periode);
        if ($existing) {
            $this->json(['error' => true, 'message' => 'Laporan untuk periode ini sudah dikirim sebelumnya.']);
            return;
        }

        // Hitung agregat dari sipw_assignment (bukan dari input PML)
        $db = Database::instance()->pdo();
        $stmt = $db->prepare("
            SELECT
                COUNT(*) as total,
                SUM(status = 'selesai') as selesai,
                SUM(status = 'proses') as proses,
                SUM(status = 'belum') as belum
            FROM sipw_assignment WHERE pengawas_id = ?
        ");
        $stmt->execute([$pmlId]);
        $stats = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Simpan laporan
        try {
            $this->model->createReport($pmlId, $periode, [
                'total'   => (int) ($stats['total'] ?? 0),
                'selesai' => (int) ($stats['selesai'] ?? 0),
                'proses'  => (int) ($stats['proses'] ?? 0),
                'belum'   => (int) ($stats['belum'] ?? 0),
            ], $catatan);

            AuditLog::log($pmlId, 'pml_report_submit', 'pml_report', json_encode($stats));

            $this->json(['success' => true, 'message' => 'Laporan berhasil dikirim.']);
        } catch (\Throwable $e) {
            $this->json(['error' => true, 'message' => 'Gagal menyimpan laporan: ' . $e->getMessage()]);
        }
    }

    private function exportExcel(): void
    {
        $periode = $_GET['periode'] ?? date('Y-m');
        $kdkec   = $_GET['kdkec'] ?? '';

        $rows = $this->model->getExportData($periode, $kdkec ?: null);

        $importDir = dirname(__DIR__, 2) . '/storage/import';
        if (!is_dir($importDir)) {
            mkdir($importDir, 0755, true);
        }

        $label = $kdkec ? "kec_{$kdkec}" : 'seluruh';
        $fileName = "laporan_pml_{$label}_{$periode}_" . date('Ymd') . '.xlsx';
        $tempFile = $importDir . '/' . $fileName;

        $options = new Options();
        $options->setTempFolder($importDir);
        $writer = new Writer($options);
        $writer->openToFile($tempFile);
        $writer->addRow(Row::fromValues(['No', 'Nama PML', 'Username', 'Email', 'Total Alokasi', 'Selesai', 'Proses', 'Belum']));

        $no = 1;
        foreach ($rows as $r) {
            $writer->addRow(Row::fromValues([
                $no++,
                $r['nama_lengkap'],
                $r['username'],
                $r['email'],
                (int) ($r['total_assigned'] ?? 0),
                (int) ($r['selesai'] ?? 0),
                (int) ($r['proses'] ?? 0),
                (int) ($r['belum'] ?? 0),
            ]));
        }

        $writer->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($tempFile));
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        readfile($tempFile);
        unlink($tempFile);
        exit;
    }
}
