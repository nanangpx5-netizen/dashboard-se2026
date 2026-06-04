<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

/**
 * PegawaiActivityController — Halaman Rekap Aktivitas Pegawai
 *
 * Menampilkan produktivitas user `pegawai` (BPS organik) berdasarkan activity_logs.
 * Metrics:
 *   - Login frequency (last 30d)
 *   - Actions per user (top 20)
 *   - Actions by type
 *   - Idle detection (no login > 14 hari)
 *
 * Routes:
 *   GET  ?page=dashboard&sub=pegawai-activity                       → index
 *   GET  ?page=dashboard&sub=pegawai-activity&action=user-detail&id= → user detail (AJAX)
 *
 * Rekomendasi: R2.5 dari Laporan Analisis Pegawai Organik.
 */
class PegawaiActivityController extends Controller
{
    private \PDO $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = Database::instance()->pdo();
        $this->requireRole(['admin', 'operator']);
    }

    public function index(): void
    {
        $action = $_GET['action'] ?? '';
        if ($action === 'user-detail') {
            $this->userDetail();
            return;
        }
        $this->showPage();
    }

    private function showPage(): void
    {
        $days = 30;
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $pegawai = $this->pdo->query("
            SELECT id, username, nama_lengkap, email, status_akun,
                   kecamatan_bertugas, last_login_at, created_at
            FROM users
            WHERE role = 'pegawai'
            ORDER BY id
        ")->fetchAll();

        $summary = [
            'total_pegawai'        => count($pegawai),
            'active_pegawai'       => 0,
            'idle_pegawai'         => 0,
            'never_login'          => 0,
            'total_actions_30d'    => 0,
            'login_count_30d'      => 0,
            'non_login_actions_30d'=> 0,
        ];

        $perUser = [];
        foreach ($pegawai as $u) {
            $uRow = [
                'id'              => $u['id'],
                'username'        => $u['username'],
                'nama_lengkap'    => $u['nama_lengkap'],
                'status_akun'     => $u['status_akun'],
                'kecamatan'       => $u['kecamatan_bertugas'],
                'last_login_at'   => $u['last_login_at'],
                'actions_30d'     => 0,
                'logins_30d'      => 0,
                'distinct_ip'     => 0,
                'first_action_at' => null,
                'last_action_at'  => null,
            ];

            $row = $this->pdo->prepare("
                SELECT COUNT(*) AS total,
                       SUM(action = 'login') AS logins,
                       COUNT(DISTINCT ip_address) AS ips,
                       MIN(created_at) AS first_at,
                       MAX(created_at) AS last_at
                FROM activity_logs
                WHERE user_id = ? AND created_at >= ?
            ");
            $row->execute([$u['id'], $since]);
            $r = $row->fetch();
            $uRow['actions_30d']     = (int) ($r['total'] ?? 0);
            $uRow['logins_30d']      = (int) ($r['logins'] ?? 0);
            $uRow['distinct_ip']     = (int) ($r['ips'] ?? 0);
            $uRow['first_action_at'] = $r['first_at'];
            $uRow['last_action_at']  = $r['last_at'];

            if ($u['status_akun'] === 'active') $summary['active_pegawai']++;
            if ($u['last_login_at'] === null) {
                $summary['never_login']++;
            } elseif (strtotime($u['last_login_at']) < strtotime('-14 days')) {
                $summary['idle_pegawai']++;
            }

            $summary['total_actions_30d']     += $uRow['actions_30d'];
            $summary['login_count_30d']       += $uRow['logins_30d'];
            $summary['non_login_actions_30d'] += ($uRow['actions_30d'] - $uRow['logins_30d']);

            $perUser[] = $uRow;
        }

        $byAction = $this->pdo->prepare("
            SELECT action, COUNT(*) AS cnt
            FROM activity_logs al
            JOIN users u ON u.id = al.user_id
            WHERE u.role = 'pegawai' AND al.created_at >= ?
            GROUP BY action
            ORDER BY cnt DESC
            LIMIT 15
        ");
        $byAction->execute([$since]);
        $byAction = $byAction->fetchAll();

        $byDay = $this->pdo->prepare("
            SELECT DATE(al.created_at) AS day, COUNT(*) AS cnt
            FROM activity_logs al
            JOIN users u ON u.id = al.user_id
            WHERE u.role = 'pegawai' AND al.created_at >= ?
            GROUP BY DATE(al.created_at)
            ORDER BY day
        ");
        $byDay->execute([$since]);
        $byDay = $byDay->fetchAll();

        $this->data['page_title'] = 'Rekap Aktivitas Pegawai';
        $this->render('pegawai-activity/index', [
            'summary'  => $summary,
            'per_user' => $perUser,
            'by_action' => $byAction,
            'by_day'    => $byDay,
            'days'      => $days,
            'js'        => ['pegawai-activity'],
        ]);
    }

    private function userDetail(): void
    {
        $userId = (int) ($_GET['id'] ?? 0);
        if ($userId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid user id.']);
            return;
        }
        $user = $this->pdo->prepare("SELECT id, username, nama_lengkap, role, last_login_at FROM users WHERE id = ? AND role = 'pegawai'");
        $user->execute([$userId]);
        $user = $user->fetch();
        if (!$user) {
            $this->json(['success' => false, 'message' => 'User not found or not a pegawai.']);
            return;
        }
        $rows = $this->pdo->prepare("
            SELECT action, module, detail, ip_address, created_at
            FROM activity_logs
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 100
        ");
        $rows->execute([$userId]);
        $this->json(['success' => true, 'user' => $user, 'logs' => $rows->fetchAll()]);
    }
}
