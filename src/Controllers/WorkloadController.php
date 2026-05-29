<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\WorkloadModel;

class WorkloadController extends Controller
{
    private WorkloadModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new WorkloadModel();
    }

    public function index(): void
    {
        $role   = $_GET['role'] ?? null;
        $kdkec  = $_GET['kdkec'] ?? null;

        $ranking      = $this->model->getRanking($role, $kdkec);
        $kecamatan    = $this->model->getKecamatan();
        $roles        = $this->model->getAvailableRoles();

        $chartLabels = [];
        $chartMuatan = [];
        foreach (array_slice($ranking, 0, 10) as $r) {
            $chartLabels[] = $r['username'];
            $chartMuatan[] = (int) $r['total_muatan'];
        }

        $this->render('workload/index', [
            'title'       => 'Beban Kerja Petugas',
            'ranking'     => $ranking,
            'kecamatan'   => $kecamatan,
            'roles'       => $roles,
            'chartLabels' => json_encode($chartLabels, JSON_UNESCAPED_UNICODE),
            'chartMuatan' => json_encode($chartMuatan),
            'filterRole'  => $role,
            'filterKdkec' => $kdkec,
        ]);
    }

    public function detail(): void
    {
        $id   = (int) ($_GET['id'] ?? 0);
        $role = $_GET['role'] ?? '';

        if (!$id || !in_array($role, ['pcl', 'pml', 'task_force'], true)) {
            $this->json(['success' => false, 'message' => 'Parameter tidak valid']);
            return;
        }

        $kdkec    = $_GET['kdkec'] ?? null;
        $detail   = $this->model->getDetail($id, $role, $kdkec);

        $summary = [
            'jumlah_sls'  => count($detail),
            'total_kk'    => array_sum(array_column($detail, 'kk')),
            'total_usaha' => array_sum(array_column($detail, 'usaha')),
            'total_muatan' => array_sum(array_column($detail, 'muatan')),
        ];

        $this->json(['success' => true, 'data' => $detail, 'summary' => $summary]);
    }
}
