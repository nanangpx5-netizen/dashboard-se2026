<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Helpers\AuditLog;
use App\Helpers\Session;
use App\Helpers\Security;
use App\Models\UserModel;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Options as ReaderOptions;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

class PclPmlTfController extends Controller
{
    private const ROLES = ['pcl', 'pml', 'task_force'];
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    public function index(): void
    {
        $this->requireRole('admin');

        $action = $_GET['action'] ?? '';

        if ($action === 'template') {
            $this->downloadTemplate();
            return;
        }

        if ($action === 'download') {
            $this->handleDownload();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->showPage();
            return;
        }

        match ($action) {
            'create'        => $this->handleCreate(),
            'edit'          => $this->handleEdit(),
            'toggle-status' => $this->handleToggleStatus(),
            'reset-password' => $this->handleResetPassword(),
            'import_upload' => $this->handleImportUpload(),
            'import_process' => $this->handleImportProcess(),
            default         => $this->redirect('?page=dashboard&sub=petugas-lapangan'),
        };
    }

    private function showPage(): void
    {
        $roleFilter = $_GET['role'] ?? '';

        $users = $this->userModel->getUsers(self::ROLES, $roleFilter);
        $roleCounts = $this->userModel->getRoleCounts(self::ROLES);

        $importPreview = Session::get('import_pcl_preview');

        $this->data['page_title'] = 'Petugas Lapangan (PCL/PML/TF)';
        $this->render('petugas-lapangan/list', [
            'users'         => $users,
            'role_counts'   => $roleCounts,
            'selected_role' => $roleFilter,
            'import_preview' => $importPreview,
            'js'            => ['petugas-lapangan'],
        ]);
    }

    private function handleCreate(): void
    {
        $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';

        $errors = [];
        if ($namaLengkap === '') $errors[] = 'Nama lengkap wajib diisi.';
        if (strlen($username) < 3) $errors[] = 'Username minimal 3 karakter.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
        if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
        if (!in_array($role, self::ROLES, true)) $errors[] = 'Role tidak valid.';

        if ($this->userModel->existsByUsername($username)) {
            $errors[] = "Username '{$username}' sudah digunakan.";
        }

        if (!empty($errors)) {
            Session::flash('error', implode(' ', $errors));
            $this->redirect('?page=dashboard&sub=petugas-lapangan');
            return;
        }

        $newId = $this->userModel->create([
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'status_akun' => 'active',
            'nama_lengkap' => $namaLengkap,
        ]);

        AuditLog::petugasChange('create', $newId, null, [
            'nama_lengkap' => $namaLengkap,
            'username' => $username,
            'email' => $email,
            'role' => $role,
        ]);

        Session::flash('success', "{$role}: {$namaLengkap} berhasil ditambahkan.");
        $this->redirect('?page=dashboard&sub=petugas-lapangan');
    }

    private function handleEdit(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';

        if ($id <= 0 || $namaLengkap === '') {
            Session::flash('error', 'Data tidak valid.');
            $this->redirect('?page=dashboard&sub=petugas-lapangan');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Email tidak valid.');
            $this->redirect('?page=dashboard&sub=petugas-lapangan');
            return;
        }

        if (!in_array($role, self::ROLES, true)) {
            Session::flash('error', 'Role tidak valid.');
            $this->redirect('?page=dashboard&sub=petugas-lapangan');
            return;
        }

        $before = $this->userModel->findById($id);
        $this->userModel->update($id, [
            'nama_lengkap' => $namaLengkap,
            'email' => $email,
            'role' => $role,
        ]);

        AuditLog::petugasChange('update', $id, $before, [
            'nama_lengkap' => $namaLengkap,
            'email' => $email,
            'role' => $role,
        ]);

        Session::flash('success', 'Data petugas berhasil diperbarui.');
        $this->redirect('?page=dashboard&sub=petugas-lapangan');
    }

    private function handleToggleStatus(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';

        if ($id <= 0 || !in_array($newStatus, ['active', 'inactive'], true)) {
            Session::flash('error', 'Parameter tidak valid.');
            $this->redirect('?page=dashboard&sub=petugas-lapangan');
            return;
        }

        $before = $this->userModel->findById($id);
        $this->userModel->update($id, ['status_akun' => $newStatus]);

        AuditLog::petugasChange('toggle_status', $id, $before, [
            'status_akun' => $newStatus,
        ]);

        $label = $newStatus === 'active' ? 'diaktifkan' : 'dinonaktifkan';
        Session::flash('success', "Petugas berhasil {$label}.");
        $this->redirect('?page=dashboard&sub=petugas-lapangan');
    }

    private function handleResetPassword(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $password = $_POST['password'] ?? '';

        if ($id <= 0 || strlen($password) < 6) {
            Session::flash('error', 'Password minimal 6 karakter.');
            $this->redirect('?page=dashboard&sub=petugas-lapangan');
            return;
        }

        $before = $this->userModel->findById($id);
        $this->userModel->updatePassword($id, $password);

        AuditLog::petugasChange('reset_password', $id, $before, [
            'password_reset' => true,
        ]);

        Session::flash('success', 'Password berhasil direset.');
        $this->redirect('?page=dashboard&sub=petugas-lapangan');
    }

    private function downloadTemplate(): void
    {
        $importDir = dirname(__DIR__, 2) . '/storage/import';
        if (!is_dir($importDir)) {
            mkdir($importDir, 0755, true);
        }
        $tempFile = $importDir . '/template_import_pclpmltf.xlsx';
        $headers = ['Nama Lengkap', 'Username', 'Email', 'Password', 'Role'];

        $options = new Options();
        $options->setTempFolder($importDir);
        $writer = new Writer($options);
        $writer->openToFile($tempFile);
        $writer->addRow(Row::fromValues($headers));
        $writer->addRow(Row::fromValues(['', '', '', '', 'pcl / pml / task_force']));
        $writer->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="template_import_pclpmltf.xlsx"');
        header('Content-Length: ' . filesize($tempFile));
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        readfile($tempFile);
        unlink($tempFile);
        exit;
    }

    private function handleDownload(): void
    {
        $role = $_GET['role'] ?? '';
        if (!in_array($role, ['pcl', 'pml'], true)) {
            Session::flash('error', 'Role tidak valid untuk diunduh.');
            $this->redirect('?page=dashboard&sub=petugas-lapangan');
            return;
        }

        $users = $this->userModel->getUsers([$role], $role);
        
        $storagePath = dirname(__DIR__, 2) . '/storage/export';
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
        
        $filename = 'data_' . $role . '_' . date('Ymd_His') . '.xlsx';
        $tempFile = $storagePath . '/' . $filename;
        
        $headers = ['ID', 'Nama Lengkap', 'Username', 'Email', 'Role', 'Status', 'Dibuat Pada'];

        $options = new Options();
        $options->setTempFolder($storagePath);
        $writer = new Writer($options);
        $writer->openToFile($tempFile);
        $writer->addRow(Row::fromValues($headers));
        
        foreach ($users as $u) {
            $writer->addRow(Row::fromValues([
                $u['id'],
                $u['nama_lengkap'],
                $u['username'],
                $u['email'],
                strtoupper($u['role']),
                $u['status_akun'],
                $u['created_at']
            ]));
        }
        
        $writer->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tempFile));
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        readfile($tempFile);
        unlink($tempFile);
        exit;
    }

    private function handleImportUpload(): void
    {
        $file = $_FILES['import_file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Pilih file Excel terlebih dahulu.');
            $this->redirect('?page=dashboard&sub=petugas-lapangan');
            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            Session::flash('error', 'Format file harus XLSX.');
            $this->redirect('?page=dashboard&sub=petugas-lapangan');
            return;
        }

        $storagePath = dirname(__DIR__, 2) . '/storage/import';
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
        $dest = $storagePath . '/pcl_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.xlsx';
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            Session::flash('error', 'Gagal menyimpan file.');
            $this->redirect('?page=dashboard&sub=petugas-lapangan');
            return;
        }

        try {
            $readerOptions = new ReaderOptions();
            $readerOptions->setTempFolder($storagePath);
            $reader = new Reader($readerOptions);
            $reader->open($dest);
            $rows = [];
            $errors = [];
            $rowNum = 0;
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rowNum++;
                    if ($rowNum === 1) continue; // skip header
                    $cells = $row->toArray();
                    $namaLengkap = trim((string) ($cells[0] ?? ''));
                    $username = trim((string) ($cells[1] ?? ''));
                    $email = trim((string) ($cells[2] ?? ''));
                    $password = trim((string) ($cells[3] ?? ''));
                    $role = strtolower(trim((string) ($cells[4] ?? '')));

                    if ($namaLengkap === '' && $username === '') continue;

                    $rowErrors = [];
                    if ($namaLengkap === '') $rowErrors[] = 'Nama Lengkap kosong';
                    if (strlen($username) < 3) $rowErrors[] = 'Username minimal 3 karakter';
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $rowErrors[] = 'Email tidak valid';
                    if (strlen($password) < 6) $rowErrors[] = 'Password minimal 6 karakter';
                    if (!in_array($role, self::ROLES, true)) $rowErrors[] = "Role harus pcl/pml/task_force";

                    if (!empty($rowErrors)) {
                        $errors[] = "Baris {$rowNum}: " . implode(', ', $rowErrors);
                    } else {
                        $rows[] = compact('namaLengkap', 'username', 'email', 'password', 'role');
                    }
                }
                break;
            }
            $reader->close();

            if (!empty($errors)) {
                @unlink($dest);
                Session::flash('error', implode('<br>', array_slice($errors, 0, 20)));
                $this->redirect('?page=dashboard&sub=petugas-lapangan');
                return;
            }

            if (empty($rows)) {
                @unlink($dest);
                Session::flash('error', 'Tidak ada data valid ditemukan.');
                $this->redirect('?page=dashboard&sub=petugas-lapangan');
                return;
            }

            Session::set('import_pcl_file', $dest);
            Session::set('import_pcl_preview', [
                'total_rows' => count($rows),
                'sample' => array_slice($rows, 0, 10),
            ]);
            Session::flash('success', "File siap: " . count($rows) . " baris akan diimport.");
        } catch (\Throwable $e) {
            @unlink($dest);
            Session::flash('error', 'Gagal membaca file. Pastikan format Excel sesuai template.');
        }
        $this->redirect('?page=dashboard&sub=petugas-lapangan');
    }

    private function handleImportProcess(): void
    {
        $filePath = Session::get('import_pcl_file');
        $preview = Session::get('import_pcl_preview');

        if (!$filePath || !is_file($filePath) || !$preview) {
            Session::flash('error', 'Data import tidak ditemukan. Upload ulang.');
            $this->redirect('?page=dashboard&sub=petugas-lapangan');
            return;
        }

        $pdo = Database::instance()->pdo();

        $berhasil = 0;
        $gagal = 0;
        $errors = [];
        $importDir = dirname(__DIR__, 2) . '/storage/import';
        $readerOptions = new ReaderOptions();
        $readerOptions->setTempFolder($importDir);
        $reader = new Reader($readerOptions);
        $reader->open($filePath);
        $rowNum = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowNum++;
                if ($rowNum === 1) continue;
                $cells = $row->toArray();
                $namaLengkap = trim((string) ($cells[0] ?? ''));
                $username = trim((string) ($cells[1] ?? ''));
                $email = trim((string) ($cells[2] ?? ''));
                $password = trim((string) ($cells[3] ?? ''));
                $role = strtolower(trim((string) ($cells[4] ?? '')));

                if ($namaLengkap === '' && $username === '') continue;

                if ($this->userModel->existsByUsername($username)) {
                    $gagal++;
                    $errors[] = "Baris {$rowNum}: Username '{$username}' sudah ada";
                    continue;
                }

                if (!in_array($role, self::ROLES, true)) {
                    $gagal++;
                    $errors[] = "Baris {$rowNum}: Role '{$role}' tidak valid";
                    continue;
                }

                $insertStmt->execute([$username, $email, Security::hashPassword($password), $role, $namaLengkap]);
                $newId = $insertStmt->rowCount() > 0 ? (int) $pdo->lastInsertId() : 0;
                if ($newId > 0) {
                    $berhasil++;
                    AuditLog::petugasChange('create', $newId, null, [
                        'nama_lengkap' => $namaLengkap,
                        'username' => $username,
                        'email' => $email,
                        'role' => $role,
                    ]);
                } else {
                    $gagal++;
                }
            }
            break;
        }
        $reader->close();
        @unlink($filePath);
        Session::remove('import_pcl_file');
        Session::remove('import_pcl_preview');

        $msg = "Import selesai: {$berhasil} berhasil";
        if ($gagal > 0) $msg .= ", {$gagal} gagal";
        $msg .= '.';
        Session::flash($gagal > 0 ? 'warning' : 'success', $msg);

        if (!empty($errors)) {
            Session::flash('error', implode('<br>', array_slice($errors, 0, 10)));
        }

        $this->redirect('?page=dashboard&sub=petugas-lapangan');
    }
}
