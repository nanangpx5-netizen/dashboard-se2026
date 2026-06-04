<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Helpers\AuditLog;
use App\Helpers\Session;
use App\Helpers\Security;
use App\Models\UserModel;

class PetugasController extends Controller
{
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

        if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
            return;
        }
        if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleEdit();
            return;
        }
        if ($action === 'toggle-status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleToggleStatus();
            return;
        }
        if ($action === 'reset-password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleResetPassword();
            return;
        }

        $roleFilter = $_GET['role'] ?? '';
        $allRoles = ['admin', 'operator', 'pegawai', 'mitra', 'pml', 'pcl', 'task_force', 'pj', 'panitia'];

        $users = $this->userModel->getUsers($allRoles, $roleFilter);
        $roleCounts = $this->userModel->getRoleCounts($allRoles);
        $kecamatanList = $this->userModel->getKecamatanList('3509');

        // Resolve nama kecamatan untuk tampilan tabel (semua user, 1 query ringan)
        $kecNameMap = [];
        foreach ($kecamatanList as $k) {
            $kecNameMap[$k['kd_kec']] = $k['nm_kec'];
        }

        $this->data['page_title'] = 'Manajemen Petugas';
        $this->render('petugas/list', [
            'users'         => $users,
            'role_counts'   => $roleCounts,
            'selected_role' => $roleFilter,
            'kecamatan_list'=> $kecamatanList,
            'kec_name_map'  => $kecNameMap,
            'js'            => ['petugas'],
        ]);
    }

    /**
     * Validasi format kecamatan_tugas.
     * Return: trimmed string (3-digit or 7-digit) atau null.
     * Return false jika format invalid.
     */
    private function validateKecamatanTugas(string $role, ?string $raw): string|false|null
    {
        $raw = trim((string) $raw);
        if ($raw === '' || $raw === '0') {
            // Boleh kosong untuk role non-pegawai
            return $role === 'pegawai' ? null : null;
        }
        if (!preg_match('/^([0-9]{3}|[0-9]{7})$/', $raw)) {
            return false;
        }
        // Jika role=pegawai, harus ada di prelist_kecamatan
        if ($role === 'pegawai' && $this->userModel->getKecamatanName($raw) === null) {
            return false;
        }
        return $raw;
    }

    private function handleCreate(): void
    {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';
        $kecamatanTugasRaw = $_POST['kecamatan_tugas'] ?? '';

        $errors = [];
        if (strlen($username) < 3) $errors[] = 'Username minimal 3 karakter.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
        if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
        if (!in_array($role, ['pcl', 'pml', 'task_force', 'operator', 'pegawai'], true)) {
            $errors[] = 'Role tidak valid.';
        }

        if ($this->userModel->existsByUsername($username)) {
            $errors[] = "Username '{$username}' sudah digunakan.";
        }

        // Kecamatan tugas — hanya untuk role=pegawai
        $kecamatanTugas = null;
        if ($role === 'pegawai') {
            $kecamatanTugas = $this->validateKecamatanTugas($role, $kecamatanTugasRaw);
            if ($kecamatanTugas === false) {
                $errors[] = 'Kecamatan tugas tidak valid (harus 3 atau 7 digit angka, dan harus ada di prelist_kecamatan).';
            }
        }

        if (!empty($errors)) {
            Session::flash('error', implode(' ', $errors));
            $this->redirect('?page=dashboard&sub=petugas');
            return;
        }

        $newId = $this->userModel->create([
            'username'         => $username,
            'email'            => $email,
            'password'         => $password,
            'role'             => $role,
            'status_akun'      => 'active',
            'kecamatan_tugas'  => $kecamatanTugas,
        ]);

        AuditLog::petugasChange('create', $newId, null, [
            'username'         => $username,
            'email'            => $email,
            'role'             => $role,
            'kecamatan_tugas'  => $kecamatanTugas,
        ]);

        Session::flash('success', "Petugas {$username} berhasil ditambahkan.");
        $this->redirect('?page=dashboard&sub=petugas');
    }

    private function handleEdit(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';
        $kecamatanTugasRaw = $_POST['kecamatan_tugas'] ?? '';

        if ($id <= 0) {
            Session::flash('error', 'ID tidak valid.');
            $this->redirect('?page=dashboard&sub=petugas');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Email tidak valid.');
            $this->redirect('?page=dashboard&sub=petugas');
            return;
        }

        if (!in_array($role, ['admin', 'operator', 'pegawai', 'mitra', 'pml', 'pcl', 'task_force'], true)) {
            Session::flash('error', 'Role tidak valid.');
            $this->redirect('?page=dashboard&sub=petugas');
            return;
        }

        // Kecamatan tugas — hanya untuk role=pegawai
        $kecamatanTugas = null;
        if ($role === 'pegawai') {
            $kecamatanTugas = $this->validateKecamatanTugas($role, $kecamatanTugasRaw);
            if ($kecamatanTugas === false) {
                Session::flash('error', 'Kecamatan tugas tidak valid (harus 3 atau 7 digit angka, dan harus ada di prelist_kecamatan).');
                $this->redirect('?page=dashboard&sub=petugas');
                return;
            }
        }

        $before = $this->userModel->findById($id);
        $this->userModel->update($id, [
            'email'            => $email,
            'role'             => $role,
            'kecamatan_tugas'  => $kecamatanTugas,
        ]);

        AuditLog::petugasChange('update', $id, $before, [
            'email'            => $email,
            'role'             => $role,
            'kecamatan_tugas'  => $kecamatanTugas,
        ]);

        Session::flash('success', 'Data petugas berhasil diperbarui.');
        $this->redirect('?page=dashboard&sub=petugas');
    }

    private function handleToggleStatus(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';

        if ($id <= 0 || !in_array($newStatus, ['active', 'inactive'], true)) {
            Session::flash('error', 'Parameter tidak valid.');
            $this->redirect('?page=dashboard&sub=petugas');
            return;
        }

        $before = $this->userModel->findById($id);
        $this->userModel->update($id, ['status_akun' => $newStatus]);

        AuditLog::petugasChange('toggle_status', $id, $before, [
            'status_akun' => $newStatus,
        ]);

        $label = $newStatus === 'active' ? 'diaktifkan' : 'dinonaktifkan';
        Session::flash('success', "Petugas berhasil {$label}.");
        $this->redirect('?page=dashboard&sub=petugas');
    }

    private function handleResetPassword(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $password = $_POST['password'] ?? '';

        if ($id <= 0 || strlen($password) < 6) {
            Session::flash('error', 'Password minimal 6 karakter.');
            $this->redirect('?page=dashboard&sub=petugas');
            return;
        }

        $before = $this->userModel->findById($id);
        $this->userModel->updatePassword($id, $password);

        AuditLog::petugasChange('reset_password', $id, $before, [
            'password_reset' => true,
        ]);

        Session::flash('success', 'Password berhasil direset.');
        $this->redirect('?page=dashboard&sub=petugas');
    }
}
