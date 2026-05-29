<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\MonitoringModel;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;

/**
 * MonitoringController — Halaman monitoring wilayah dengan DataTables server-side
 *
 * Routes:
 *   GET  ?page=dashboard&sub=monitoring             → index (halaman)
 *   GET  ?page=dashboard&sub=monitoring&action=data  → dataTable (AJAX JSON)
 *   GET  ?page=dashboard&sub=monitoring&action=export → exportExcel
 *   GET  ?page=dashboard&sub=monitoring&action=filters → filterDropdowns (AJAX JSON)
 */
class MonitoringController extends Controller
{
    private MonitoringModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new MonitoringModel();
    }

    public function index(): void
    {
        $action = $_GET['action'] ?? '';

        match ($action) {
            'data'    => $this->dataTable(),
            'export'  => $this->exportExcel(),
            'filters' => $this->filterDropdowns(),
            default   => $this->showPage(),
        };
    }

    /**
     * Tampilkan halaman monitoring
     */
    private function showPage(): void
    {
        $summary   = $this->model->getSummary();
        $kecamatan = $this->model->getKecamatan();
        $petugas   = $this->model->getPetugasLists();

        $this->data['page_title'] = 'Monitoring Wilayah';
        $this->render('monitoring/index', [
            'summary'    => $summary,
            'kecamatan'  => $kecamatan,
            'pencacah'   => $petugas['pencacah'],
            'pengawas'   => $petugas['pengawas'],
            'task_force' => $petugas['task_force'],
            'js'         => ['monitoring'],
        ]);
    }

    /**
     * DataTables server-side: return JSON
     */
    private function dataTable(): void
    {
        $draw   = (int) ($_GET['draw'] ?? 0);
        $start  = (int) ($_GET['start'] ?? 0);
        $length = (int) ($_GET['length'] ?? 25);
        $search = $_GET['search']['value'] ?? '';

        // Ambil order dari DataTables
        $order = [];
        if (!empty($_GET['order'][0])) {
            $order = [
                'column' => $_GET['order'][0]['column'],
                'dir'    => $_GET['order'][0]['dir'],
            ];
        }

        // Filters dari query string
        $filters = [
            'kdkec'      => $_GET['kdkec'] ?? '',
            'kddesa'     => $_GET['kddesa'] ?? '',
            'pencacah'   => $_GET['pencacah'] ?? '',
            'pengawas'   => $_GET['pengawas'] ?? '',
            'task_force' => $_GET['task_force'] ?? '',
            'status'     => $_GET['status'] ?? '',
        ];

        $recordsTotal    = $this->model->totalCount();
        $recordsFiltered = $this->model->filteredCount($filters, $search);
        $data            = $this->model->getDataTable($filters, $search, $start, $length, $order);

        // Format data untuk DataTables
        $rows = [];
        foreach ($data as $r) {
            $rows[] = [
                'nmkec'      => htmlspecialchars($r['nmkec']),
                'nmdesa'     => htmlspecialchars($r['nmdesa']),
                'nmsls'      => htmlspecialchars($r['nmsls']),
                'kk'         => number_format($r['kk']),
                'usaha'      => number_format($r['usaha']),
                'muatan'     => number_format($r['muatan']),
                'pencacah'   => htmlspecialchars($r['pencacah']),
                'pengawas'   => htmlspecialchars($r['pengawas']),
                'task_force' => htmlspecialchars($r['task_force']),
                'status'     => $r['status'],
                'status_badge' => $this->statusBadge($r['status']),
            ];
        }

        $this->json([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $rows,
        ]);
    }

    /**
     * Export ke Excel menggunakan OpenSpout
     */
    private function exportExcel(): void
    {
        $filters = [
            'kdkec'      => $_GET['kdkec'] ?? '',
            'kddesa'     => $_GET['kddesa'] ?? '',
            'pencacah'   => $_GET['pencacah'] ?? '',
            'pengawas'   => $_GET['pengawas'] ?? '',
            'task_force' => $_GET['task_force'] ?? '',
            'status'     => $_GET['status'] ?? '',
        ];

        $data = $this->model->exportAll($filters);

        $headerRow = Row::fromValues([
            'No', 'Kecamatan', 'Desa', 'SLS', 'KK', 'Usaha',
            'Muatan', 'Pencacah (PCL)', 'Pengawas (PML)',
            'Task Force', 'Status',
        ]);

        $writer = new Writer();
        $writer->openToBrowser('monitoring_wilayah_' . date('Ymd_His') . '.xlsx');

        $writer->addRow($headerRow);

        $no = 1;
        foreach ($data as $r) {
            $writer->addRow(Row::fromValues([
                $no++,
                $r['nmkec'],
                $r['nmdesa'],
                $r['nmsls'],
                (int) $r['kk'],
                (int) $r['usaha'],
                (int) $r['muatan'],
                $r['pencacah'],
                $r['pengawas'],
                $r['task_force'],
                $r['status'],
            ]));
        }

        $writer->close();
        exit;
    }

    /**
     * AJAX: daftar desa untuk suatu kecamatan (dropdown cascade)
     */
    private function filterDropdowns(): void
    {
        $kdkec = $_GET['kdkec'] ?? '';
        if ($kdkec === '') {
            $this->json(['success' => false, 'message' => 'kdkec required']);
            return;
        }

        $desa = $this->model->getDesa($kdkec);
        $this->json(['success' => true, 'data' => $desa]);
    }

    /**
     * HTML badge untuk status
     */
    private function statusBadge(string $status): string
    {
        $map = [
            'belum'  => 'bg-secondary',
            'proses' => 'bg-warning text-dark',
            'selesai' => 'bg-success',
        ];
        $class = $map[$status] ?? 'bg-secondary';
        $safe = htmlspecialchars($status, ENT_QUOTES, 'UTF-8');
        return "<span class=\"badge {$class}\">{$safe}</span>";
    }
}
