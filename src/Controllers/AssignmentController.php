<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Helpers\Backup;
use App\Helpers\Cache;
use App\Helpers\Session;
use App\Models\AssignmentModel;
use App\Services\AssignmentImporter;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

/**
 * AssignmentController — Modul Assignment Petugas
 *
 * Fitur:
 *   1. assign single  → POST ?action=assign
 *   2. bulk assign    → POST ?action=bulk
 *   3. edit assign    → POST ?action=edit
 *   4. remove assign  → POST ?action=remove
 *   5. filter         → GET ?kdkec=&kddesa=&status=
 *   6. search         → GET ?search= (DataTables search)
 *
 * Mapping:
 *   sipw_import (SLS) → sipw_assignment → users (pencacah/pengawas/task_force)
 */
class AssignmentController extends Controller
{
    private AssignmentModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new AssignmentModel();
    }

    public function index(): void
    {
        $action = $_GET['action'] ?? '';

        if ($action === 'template') {
            $this->downloadTemplate();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // GET: tampilkan halaman
            $filters = [
                'kdkec'  => $_GET['kdkec'] ?? '',
                'kddesa' => $_GET['kddesa'] ?? '',
                'status' => $_GET['status'] ?? '',
                'search' => $_GET['search'] ?? '',
            ];

            $pageNum        = max(1, (int) ($_GET['hal'] ?? 1));
            $perPage        = max(10, min(100, (int) ($_GET['per_page'] ?? 25)));
            $tab            = $_GET['tab'] ?? 'unassigned';

            $totalAssigned   = $this->model->countAll($filters);
            $totalUnassigned = $this->model->countUnassigned(
                $filters['kdkec'] ?: null,
                $filters['kddesa'] ?: null,
                $filters['search']
            );

            $totalRows       = $tab === 'assigned' ? $totalAssigned : $totalUnassigned;
            $totalPages      = max(1, (int) ceil($totalRows / $perPage));
            $pageNum         = min($pageNum, $totalPages);

            $assignments     = $this->model->getAllPaginated($filters, $pageNum, $perPage);
            $unassigned      = $this->model->getUnassignedPaginated(
                $filters['kdkec'] ?: null,
                $filters['kddesa'] ?: null,
                $filters['search'],
                $pageNum,
                $perPage
            );
            $kecamatan   = $this->model->getKecamatan();
            $petugas     = $this->model->getPetugas();
            $summary     = $this->model->countSummary();
            $petugasLoad = $this->model->getPetugasLoad();
            $desaList    = $filters['kdkec']
                ? $this->model->getDesa($filters['kdkec'])
                : [];

            // Petugas dropdown — single query, filter by role di client-side
            $allPetugas = $this->model->getPetugasByRole('pcl', 'pml', 'task_force', 'mitra', 'admin');
            $allPCL     = array_values(array_filter($allPetugas, fn($u) => in_array($u['role'], ['pcl', 'mitra', 'admin'])));
            $allPML     = array_values(array_filter($allPetugas, fn($u) => in_array($u['role'], ['pml', 'admin'])));
            $allTF      = array_values(array_filter($allPetugas, fn($u) => in_array($u['role'], ['task_force', 'admin'])));

            // Import preview data dari session
            $importPreview = Session::get('import_assign_preview');

            $this->data['page_title'] = 'Assignment Petugas';
            $this->render('assignment/index', [
                'assignments'     => $assignments,
                'unassigned'      => $unassigned,
                'page_num'        => $pageNum,
                'per_page'        => $perPage,
                'total_pages'     => $totalPages,
                'total_rows'      => $totalRows,
                'total_assigned'  => $totalAssigned,
                'total_unassigned' => $totalUnassigned,
                'tab'             => $tab,
                'kecamatan'       => $kecamatan,
                'desa_list'       => $desaList,
                'petugas'         => $petugas,
                'pcl_list'        => $allPCL,
                'pml_list'        => $allPML,
                'tf_list'         => $allTF,
                'summary'         => $summary,
                'petugas_load'    => $petugasLoad,
                'filters'         => $filters,
                'import_preview'  => $importPreview,
                'js'              => ['assignment'],
            ]);
            return;
        }

        // POST handlers
        match ($action) {
            'assign'        => $this->handleAssign(),
            'edit'          => $this->handleEdit(),
            'remove'        => $this->handleRemove(),
            'bulk'          => $this->handleBulk(),
            'status'        => $this->handleStatus(),
            'import_upload' => $this->handleImportUpload(),
            'import_process' => $this->handleImportProcess(),
            default         => $this->redirect('?page=dashboard&sub=assignment'),
        };
    }

    /**
     * 1. Assign single — buat assignment baru untuk satu SLS
     */
    private function handleAssign(): void
    {
        $sipwId      = (int) ($_POST['sipw_id'] ?? 0);
        $pencacahId  = $this->intOrNull($_POST['pencacah_id'] ?? null);
        $pengawasId  = $this->intOrNull($_POST['pengawas_id'] ?? null);
        $taskForceId = $this->intOrNull($_POST['task_force_id'] ?? null);

        if ($sipwId <= 0) {
            Session::flash('error', 'SLS tidak valid.');
            $this->redirect('?page=dashboard&sub=assignment');
            return;
        }

        $isUpdate = $this->model->exists($sipwId);
        $oldData = $isUpdate ? $this->model->findBySipwId($sipwId) : null;

        if ($isUpdate) {
            $this->model->update($sipwId, $pencacahId, $pengawasId, $taskForceId);
            Session::flash('success', 'Assignment berhasil diperbarui.');
        } else {
            $this->model->assign($sipwId, $pencacahId, $pengawasId, $taskForceId);
            Session::flash('success', 'Assignment baru berhasil dibuat.');
        }

        Backup::logAssignment(
            $isUpdate ? 'UPDATE' : 'INSERT',
            $sipwId,
            $oldData['id'] ?? null,
            $oldData,
            [
                'sipw_id' => $sipwId,
                'pencacah_id' => $pencacahId,
                'pengawas_id' => $pengawasId,
                'task_force_id' => $taskForceId,
                'status' => 'belum',
            ],
            $isUpdate ? 'Update assignment existing' : 'Assignment baru'
        );

        Cache::forget('dashboard_stats');
        Cache::forget('dashboard_wilayah');
        Cache::forget('dashboard_beban');

        $this->redirect('?page=dashboard&sub=assignment');
    }

    /**
     * 3. Edit assign — update petugas pada assignment existing
     */
    private function handleEdit(): void
    {
        $sipwId      = (int) ($_POST['sipw_id'] ?? 0);
        $pencacahId  = $this->intOrNull($_POST['pencacah_id'] ?? null);
        $pengawasId  = $this->intOrNull($_POST['pengawas_id'] ?? null);
        $taskForceId = $this->intOrNull($_POST['task_force_id'] ?? null);

        if ($sipwId <= 0) {
            Session::flash('error', 'SLS tidak valid.');
            $this->redirect('?page=dashboard&sub=assignment');
            return;
        }

        $oldData = $this->model->findBySipwId($sipwId);
        $this->model->update($sipwId, $pencacahId, $pengawasId, $taskForceId);

        Backup::logAssignment(
            'UPDATE',
            $sipwId,
            $oldData['id'] ?? null,
            $oldData,
            [
                'sipw_id' => $sipwId,
                'pencacah_id' => $pencacahId,
                'pengawas_id' => $pengawasId,
                'task_force_id' => $taskForceId,
            ],
            'Edit assignment petugas'
        );

        Cache::forget('dashboard_stats');
        Cache::forget('dashboard_wilayah');
        Cache::forget('dashboard_beban');
        Session::flash('success', 'Assignment berhasil diperbarui.');
        $this->redirect('?page=dashboard&sub=assignment');
    }

    private function handleRemove(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $oldData = $this->model->findById($id);
            $this->model->delete($id);

            if ($oldData) {
                Backup::logAssignment(
                    'DELETE',
                    $oldData['sipw_id'],
                    $id,
                    $oldData,
                    null,
                    'Hapus assignment'
                );
            }

            Cache::forget('dashboard_stats');
            Cache::forget('dashboard_wilayah');
            Cache::forget('dashboard_beban');
            Session::flash('info', 'Assignment berhasil dihapus.');
        }
        $this->redirect('?page=dashboard&sub=assignment');
    }

    private function handleBulk(): void
    {
        $kdkec       = $_POST['kdkec'] ?? '';
        $pencacahId  = $this->intOrNull($_POST['pencacah_id'] ?? null);
        $pengawasId  = $this->intOrNull($_POST['pengawas_id'] ?? null);
        $taskForceId = $this->intOrNull($_POST['task_force_id'] ?? null);

        if (empty($kdkec)) {
            Session::flash('error', 'Pilih kecamatan terlebih dahulu.');
            $this->redirect('?page=dashboard&sub=assignment');
            return;
        }

        try {
            $count = $this->model->bulkAssign($kdkec, $pencacahId, $pengawasId, $taskForceId);
            if ($count > 0) {
                Backup::logAssignment(
                    'INSERT',
                    0,
                    null,
                    null,
                    [
                        'kdkec' => $kdkec,
                        'pencacah_id' => $pencacahId,
                        'pengawas_id' => $pengawasId,
                        'task_force_id' => $taskForceId,
                        'count' => $count,
                    ],
                    "Bulk assignment {$count} SLS di kec. {$kdkec}"
                );
                Cache::forget('dashboard_stats');
                Cache::forget('dashboard_wilayah');
                Cache::forget('dashboard_beban');
                Session::flash('success', "Bulk assignment selesai: {$count} SLS di-assign.");
            } else {
                Session::flash('info', 'Semua SLS di kecamatan ini sudah di-assign.');
            }
        } catch (\Throwable $e) {
            Session::flash('error', 'Gagal bulk assign: ' . $e->getMessage());
        }

        $this->redirect('?page=dashboard&sub=assignment');
    }

    private function handleStatus(): void
    {
        $id     = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if ($id > 0 && in_array($status, ['belum', 'proses', 'selesai'], true)) {
            $oldData = $this->model->findById($id);
            $this->model->updateStatus($id, $status);

            if ($oldData) {
                Backup::logAssignment(
                    'STATUS_CHANGE',
                    $oldData['sipw_id'],
                    $id,
                    ['status' => $oldData['status']],
                    ['status' => $status],
                    "Ubah status: {$oldData['status']} → {$status}"
                );
            }

            Cache::forget('dashboard_stats');
            Cache::forget('dashboard_wilayah');
            Cache::forget('dashboard_beban');
            Session::flash('success', 'Status berhasil diperbarui.');
        }

        $this->redirect('?page=dashboard&sub=assignment');
    }

    private function intOrNull(mixed $val): ?int
    {
        if ($val === null || $val === '' || $val === 0 || $val === '0') return null;
        return (int) $val;
    }

    private function handleImportUpload(): void
    {
        $file = $_FILES['import_file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Pilih file Excel terlebih dahulu.');
            $this->redirect('?page=dashboard&sub=assignment');
            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            Session::flash('error', 'Format file harus XLSX.');
            $this->redirect('?page=dashboard&sub=assignment');
            return;
        }

        $storagePath = dirname(__DIR__, 2) . '/storage/import';
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
        $dest = $storagePath . '/assign_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.xlsx';
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            Session::flash('error', 'Gagal menyimpan file.');
            $this->redirect('?page=dashboard&sub=assignment');
            return;
        }

        try {
            $importer = new AssignmentImporter();
            $preview = $importer->preview($dest);
        } catch (\Throwable $e) {
            @unlink($dest);
            Session::flash('error', 'Gagal membaca file: ' . $e->getMessage());
            $this->redirect('?page=dashboard&sub=assignment');
            return;
        }

        if (!empty($preview['errors'])) {
            @unlink($dest);
            Session::flash('error', implode('<br>', $preview['errors']));
            $this->redirect('?page=dashboard&sub=assignment');
            return;
        }

        Session::set('import_assign_file', $dest);
        Session::set('import_assign_preview', $preview);
        Session::flash('success', "File siap: {$preview['total_rows']} baris ditemukan.");
        $this->redirect('?page=dashboard&sub=assignment');
    }

    private function handleImportProcess(): void
    {
        $filePath = Session::get('import_assign_file');
        $preview  = Session::get('import_assign_preview');

        if (!$filePath || !is_file($filePath) || !$preview) {
            Session::flash('error', 'Data import tidak ditemukan. Upload ulang.');
            $this->redirect('?page=dashboard&sub=assignment');
            return;
        }

        $user = Session::get('user');
        $userId = (int) ($user['id'] ?? 0);
        $ipAddr = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        try {
            $importer = new AssignmentImporter();
            $result = $importer->import($filePath, $userId, $ipAddr);
        } catch (\Throwable $e) {
            Session::flash('error', 'Import gagal: ' . $e->getMessage());
            $this->redirect('?page=dashboard&sub=assignment');
            return;
        } finally {
            @unlink($filePath);
            Session::remove('import_assign_file');
            Session::remove('import_assign_preview');
        }

        Cache::forget('dashboard_stats');
        Cache::forget('dashboard_wilayah');
        Cache::forget('dashboard_beban');

        $msg = "Import selesai: {$result['stats']['berhasil']} berhasil" .
               ($result['stats']['gagal'] > 0 ? ", {$result['stats']['gagal']} gagal" : '') . '.';
        Session::flash($result['stats']['gagal'] > 0 ? 'warning' : 'success', $msg);
        $this->redirect('?page=dashboard&sub=assignment');
    }

    private function downloadTemplate(): void
    {
        $importDir = dirname(__DIR__, 2) . '/storage/import';
        if (!is_dir($importDir)) {
            mkdir($importDir, 0755, true);
        }
        $tempFile = $importDir . '/template_import_assignment.xlsx';
        $headers  = ['nmsls', 'nmdesa', 'nmkec', 'pcl', 'pml', 'task_force'];

        $options = new Options();
        $options->setTempFolder($importDir);
        $writer = new Writer($options);
        $writer->openToFile($tempFile);
        $writer->addRow(Row::fromValues($headers));

        $pdo       = Database::instance()->pdo();
        $kecamatan = $this->model->getKecamatan();
        $petugas   = $this->model->getPetugasByRole('pcl', 'pml', 'task_force', 'mitra', 'admin');

        $sampleRows = 0;
        foreach ($kecamatan as $kec) {
            $desaList = $this->model->getDesa($kec['kdkec']);
            foreach ($desaList as $desa) {
                $stmt = $pdo->query(
                    "SELECT nmsls FROM sipw_import WHERE kddesa = " . (int) $desa['kddesa'] . " LIMIT 2"
                );
                while ($row = $stmt->fetch()) {
                    $writer->addRow(Row::fromValues([
                        $row['nmsls'],
                        $desa['nmdesa'],
                        $kec['nmkec'],
                        '',
                        '',
                        '',
                    ]));
                    $sampleRows++;
                    if ($sampleRows >= 10) break 3;
                }
            }
        }

        if (!empty($petugas)) {
            $writer->addRow(Row::fromValues(['', '', '', '— isi username petugas —', '— isi username petugas —', '— isi username petugas —']));
        }

        $writer->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="template_import_assignment.xlsx"');
        header('Content-Length: ' . filesize($tempFile));
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        readfile($tempFile);
        unlink($tempFile);
        exit;
    }
}
