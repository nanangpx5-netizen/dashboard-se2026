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
            'data'           => $this->dataTable(),
            'export'         => $this->exportExcel(),
            'filters'        => $this->filterDropdowns(),
            'kecamatan-summary' => $this->kecamatanSummary(),
            'desa-summary'   => $this->desaSummary(),
            'sls-data'       => $this->slsData(),
            'non-sls-data'   => $this->nonSlsData(),
            default          => $this->showPage(),
        };
    }

    /**
     * Tampilkan halaman monitoring
     */
    private function showPage(): void
    {
        $scope = $this->getKecamatanScope();
        $kdkec = '';
        if ($scope !== null && strlen($scope) === 7) {
            $kdkec = substr($scope, -3);
        }

        $summary   = $this->model->getSummary();
        $kecamatan = $this->model->getKecamatan();
        $petugas   = $this->model->getPetugasLists();
        $prelistKec = $this->model->getPrelistKecamatan('3509');

        // Data untuk widget monitoring
        $kecSummary   = $this->model->getKecamatanSummary();
        $totalPrelist = $this->model->countPrelistSls('3509', '', $kdkec);

        $this->data['page_title'] = 'Monitoring Wilayah';
        $this->data['kecamatan_scope'] = $scope;
        $this->render('monitoring/index', [
            'summary'       => $summary,
            'kecamatan'     => $kecamatan,
            'kec_summary'   => $kecSummary,
            'total_prelist' => $totalPrelist,
            'prelist_kec'   => $prelistKec,
            'pencacah'      => $petugas['pencacah'],
            'pengawas'      => $petugas['pengawas'],
            'task_force'    => $petugas['task_force'],
            'js'            => ['monitoring'],
        ]);
    }

    /**
     * AJAX JSON: summary per kecamatan
     */
    private function kecamatanSummary(): void
    {
        $filters = $this->applyKecamatanScope([
            'kdkec' => $_GET['kdkec'] ?? '',
        ]);
        $data = $this->model->getKecamatanSummary($filters);
        $this->json(['success' => true, 'data' => $data]);
    }

    /**
     * AJAX JSON: summary per desa (filter by kdkec)
     */
    private function desaSummary(): void
    {
        $kdkec = $this->getKecamatanScope() ?? ($_GET['kdkec'] ?? '');
        if ($kdkec === '') {
            $this->json(['success' => false, 'message' => 'Parameter kdkec wajib']);
            return;
        }
        $data = $this->model->getDesaSummary($kdkec);
        $this->json(['success' => true, 'data' => $data]);
    }

    /**
     * AJAX JSON: DataTables format untuk assigned SLS
     */
    private function slsData(): void
    {
        $draw   = (int) ($_GET['draw'] ?? 0);
        $start  = (int) ($_GET['start'] ?? 0);
        $length = (int) ($_GET['length'] ?? 25);

        $filters = $this->applyKecamatanScope([
            'kdkec'      => $_GET['kdkec'] ?? '',
            'kddesa'     => $_GET['kddesa'] ?? '',
            'pencacah'   => $_GET['pencacah'] ?? '',
            'pengawas'   => $_GET['pengawas'] ?? '',
            'task_force' => $_GET['task_force'] ?? '',
            'status'     => $_GET['status'] ?? '',
            'search'     => $_GET['search']['value'] ?? '',
        ]);

        $recordsTotal    = $this->model->countSlsAssigned($filters);
        $recordsFiltered = $recordsTotal;
        $data            = $this->model->getSlsAssigned($filters, $start, $length);

        $rows = [];
        foreach ($data as $r) {
            $rows[] = [
                'id'          => $r['id'],
                'nmkec'       => htmlspecialchars($r['nmkec']),
                'nmdesa'      => htmlspecialchars($r['nmdesa']),
                'nmsls'       => htmlspecialchars($r['nmsls']),
                'kk'          => (int) $r['kk'],
                'usaha'       => (int) $r['usaha'],
                'muatan'      => (int) $r['muatan'],
                'pencacah'    => htmlspecialchars($r['pencacah']),
                'pengawas'    => htmlspecialchars($r['pengawas']),
                'task_force'  => htmlspecialchars($r['task_force']),
                'status'      => $r['status'],
                'status_badge' => $this->statusBadge($r['status']),
                'tgl_assign'  => $r['tgl_assign'] ?? '-',
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
     * AJAX JSON: DataTables format untuk prelist SLS (non-SLS)
     */
    private function nonSlsData(): void
    {
        $draw   = (int) ($_GET['draw'] ?? 0);
        $start  = (int) ($_GET['start'] ?? 0);
        $length = (int) ($_GET['length'] ?? 25);
        $search = $_GET['search']['value'] ?? '';

        // Extract kd_kec (3-digit) from session scope (7-digit, e.g. '3509180' → '180')
        $kdkec = '';
        $scope = $this->getKecamatanScope();
        if ($scope !== null && strlen($scope) === 7) {
            $kdkec = substr($scope, -3);
        }

        $recordsTotal    = $this->model->countPrelistSls('3509', '', $kdkec);
        $recordsFiltered = $this->model->countPrelistSls('3509', $search, $kdkec);
        $data            = $this->model->getPrelistSls('3509', $search, $start, $length, $kdkec);

        $rows = [];
        foreach ($data as $r) {
            $rows[] = [
                'idsls'           => htmlspecialchars($r['idsls']),
                'kd_kec'          => htmlspecialchars($r['kd_kec']),
                'nm_kec'          => htmlspecialchars($r['nm_kec']),
                'nm_desa'         => htmlspecialchars($r['nm_desa']),
                'nama_sls'        => htmlspecialchars($r['nama_sls']),
                'jml_kk'          => (int) ($r['jml_kk'] ?? 0),
                'utp'             => (int) ($r['utp'] ?? 0),
                'muatan_rs'       => (int) ($r['muatan_rs'] ?? 0),
                'subsektor'       => (int) ($r['subsektor'] ?? 0),
                'usaha_se2016'    => (int) ($r['usaha_se2016'] ?? 0),
                'usaha_wilkerstat'=> (int) ($r['usaha_wilkerstat'] ?? 0),
                'imported_at'     => $r['imported_at'] ?? '-',
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
        $filters = $this->applyKecamatanScope([
            'kdkec'      => $_GET['kdkec'] ?? '',
            'kddesa'     => $_GET['kddesa'] ?? '',
            'pencacah'   => $_GET['pencacah'] ?? '',
            'pengawas'   => $_GET['pengawas'] ?? '',
            'task_force' => $_GET['task_force'] ?? '',
            'status'     => $_GET['status'] ?? '',
        ]);

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
        $filters = $this->applyKecamatanScope([
            'kdkec'      => $_GET['kdkec'] ?? '',
            'kddesa'     => $_GET['kddesa'] ?? '',
            'pencacah'   => $_GET['pencacah'] ?? '',
            'pengawas'   => $_GET['pengawas'] ?? '',
            'task_force' => $_GET['task_force'] ?? '',
            'status'     => $_GET['status'] ?? '',
        ]);

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
        $kdkec = $this->getKecamatanScope() ?? ($_GET['kdkec'] ?? '');
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
