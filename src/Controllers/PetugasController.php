<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Helpers\AuditLog;
use App\Helpers\Session;
use App\Helpers\Security;

class PetugasController extends Controller
{
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

        $pdo = Database::instance()->pdo();

        $role = $_GET['role'] ?? '';
        $params = [];
        $sql = "SELECT id, email, username, nama_lengkap, role, status_akun, last_login_at, created_at FROM users WHERE 1=1";
        if ($role !== '' && in_array($role, ['admin', 'operator', 'pegawai', 'mitra', 'pml', 'pcl', 'task_force', 'pj', 'panitia'], true)) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }
        $sql .= " ORDER BY id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        $roleCounts = $pdo->query("
            SELECT role, COUNT(*) as total,
                   SUM(CASE WHEN status_akun = 'active' THEN 1 ELSE 0 END) as aktif
            FROM users
            GROUP BY role ORDER BY role
        ")->fetchAll();

        $this->data['page_title'] = 'Manajemen Petugas';
        $this->render('petugas/list', [
            'users'      => $users,
            'role_counts' => $roleCounts,
            'selected_role' => $role,
            'js'         => ['petugas'],
        ]);
    }

    private function handleCreate(): void
    {
        $pdo = Database::instance()->pdo();
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';

        $errors = [];
        if (strlen($username) < 3) $errors[] = 'Username minimal 3 karakter.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
        if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
        if (!in_array($role, ['pcl', 'pml', 'task_force', 'operator', 'pegawai'], true)) {
            $errors[] = 'Role tidak valid.';
        }

        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetch()) $errors[] = "Username '{$username}' sudah digunakan.";

        if (!empty($errors)) {
            Session::flash('error', implode(' ', $errors));
            $this->redirect('?page=dashboard&sub=petugas');
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, role, status_akun)
            VALUES (?, ?, ?, ?, 'active')
        ");
        $stmt->execute([$username, $email, Security::hashPassword($password), $role]);

        AuditLog::petugasChange('create', (int) $pdo->lastInsertId(), null, [
            'username' => $username,
            'email' => $email,
            'role' => $role,
        ]);

        Session::flash('success', "Petugas {$username} berhasil ditambahkan.");
        $this->redirect('?page=dashboard&sub=petugas');
    }

    private function handleEdit(): void
    {
        $pdo = Database::instance()->pdo();
        $id = (int) ($_POST['id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';

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

        $old = $pdo->prepare("SELECT email, role FROM users WHERE id = ?");
        $old->execute([$id]);
        $before = $old->fetch();

        $stmt = $pdo->prepare("UPDATE users SET email = ?, role = ? WHERE id = ?");
        $stmt->execute([$email, $role, $id]);

        AuditLog::petugasChange('update', $id, $before ?: null, [
            'email' => $email,
            'role' => $role,
        ]);

        Session::flash('success', 'Data petugas berhasil diperbarui.');
        $this->redirect('?page=dashboard&sub=petugas');
    }

    private function handleToggleStatus(): void
    {
        $pdo = Database::instance()->pdo();
        $id = (int) ($_POST['id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';

        if ($id <= 0 || !in_array($newStatus, ['active', 'inactive'], true)) {
            Session::flash('error', 'Parameter tidak valid.');
            $this->redirect('?page=dashboard&sub=petugas');
            return;
        }

        $old = $pdo->prepare("SELECT username, status_akun FROM users WHERE id = ?");
        $old->execute([$id]);
        $before = $old->fetch();

        $stmt = $pdo->prepare("UPDATE users SET status_akun = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);

        AuditLog::petugasChange('toggle_status', $id, $before ?: null, [
            'status_akun' => $newStatus,
        ]);

        $label = $newStatus === 'active' ? 'diaktifkan' : 'dinonaktifkan';
        Session::flash('success', "Petugas berhasil {$label}.");
        $this->redirect('?page=dashboard&sub=petugas');
    }

    private function handleResetPassword(): void
    {
        $pdo = Database::instance()->pdo();
        $id = (int) ($_POST['id'] ?? 0);
        $password = $_POST['password'] ?? '';

        if ($id <= 0 || strlen($password) < 6) {
            Session::flash('error', 'Password minimal 6 karakter.');
            $this->redirect('?page=dashboard&sub=petugas');
            return;
        }

        $old = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $old->execute([$id]);
        $before = $old->fetch();

        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([Security::hashPassword($password), $id]);

        AuditLog::petugasChange('reset_password', $id, $before ?: null, [
            'password_reset' => true,
        ]);

        Session::flash('success', 'Password berhasil direset.');
        $this->redirect('?page=dashboard&sub=petugas');
    }
}
