<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Helpers\Session;

class WilayahController extends Controller
{
    public function index(): void
    {
        $action = $_GET['action'] ?? '';

        if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleEdit();
            return;
        }

        $pdo = Database::instance()->pdo();
        $data = $pdo->query("
            SELECT wk.*,
                COALESCE(s.total_sls, 0)      AS total_sls,
                COALESCE(s.assigned_sls, 0)   AS assigned_sls,
                COALESCE(s.completed_sls, 0)  AS completed_sls
            FROM wilayah_kerja wk
            LEFT JOIN (
                SELECT si.kdkec,
                       COUNT(*) AS total_sls,
                       SUM(CASE WHEN sa.id IS NOT NULL THEN 1 ELSE 0 END) AS assigned_sls,
                       SUM(CASE WHEN sa.status = 'selesai' THEN 1 ELSE 0 END) AS completed_sls
                FROM sipw_import si
                LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id
                GROUP BY si.kdkec
            ) s ON s.kdkec = wk.kode_kecamatan
            ORDER BY wk.nama_kecamatan
        ")->fetchAll();

        $this->data['page_title'] = 'Data Wilayah';
        $this->render('wilayah/list', ['wilayah' => $data]);
    }

    private function handleEdit(): void
    {
        $pdo = Database::instance()->pdo();
        $id = (int) ($_POST['id'] ?? 0);

        $kebutuhanPcl = (int) ($_POST['kebutuhan_pcl'] ?? 0);
        $kebutuhanPml = (int) ($_POST['kebutuhan_pml'] ?? 0);

        $stmt = $pdo->prepare("UPDATE wilayah_kerja SET kebutuhan_pcl = ?, kebutuhan_pml = ? WHERE id = ?");
        $stmt->execute([$kebutuhanPcl, $kebutuhanPml, $id]);

        Session::flash('success', 'Data wilayah berhasil diperbarui.');
        $this->redirect('?page=dashboard&sub=wilayah');
    }
}
