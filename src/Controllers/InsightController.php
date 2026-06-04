<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\InsightModel;

/**
 * InsightController — Halaman Insight & Analisa
 *
 * Routes:
 *   GET  ?page=dashboard&sub=insight                       → index (halaman utama)
 *   GET  ?page=dashboard&sub=insight&action=anomali-detail  → detail anomali (AJAX JSON)
 *   GET  ?page=dashboard&sub=insight&action=export          → export Excel
 */
class InsightController extends Controller
{
    private InsightModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new InsightModel();
    }

    public function index(): void
    {
        $action = $_GET['action'] ?? '';
        match ($action) {
            'anomali-detail'  => $this->anomaliDetail(),
            'sub-sls-detail'  => $this->subSlsDetail(),
            'export'          => $this->exportExcel(),
            default           => $this->showPage(),
        };
    }

    private function subSlsDetail(): void
    {
        $idsls = $_GET['idsls'] ?? '';
        if (strlen($idsls) !== 14) {
            $this->json(['success' => false, 'message' => 'ID SLS tidak valid.']);
            return;
        }
        $rows = $this->model->getDeltaSlsRows($idsls);
        $this->json(['success' => true, 'data' => $rows, 'total' => count($rows)]);
    }

    private function showPage(): void
    {
        $summary = $this->model->getExecutiveSummary();
        $anomali = $this->model->getAnomaliPerKecamatan('09');
        $beban   = $this->model->getBebanKerjaPerKecamatan('09');
        $distribusi = $this->model->getDistribusiMuatan('09');
        $coverage   = $this->model->getCoverageGap('3509');
        $rekomendasi = $this->model->getRekomendasi($summary, $anomali, $beban);
        $userPool   = $this->model->getUserPool();
        $quality    = $this->model->getDataQuality();

        $totalAnomaliSls = array_sum(array_column($anomali, 'mu_zero'));
        $totalSls        = (int) ($summary['total_sls'] ?? 0);
        $anomaliPct      = $totalSls > 0 ? round(($totalAnomaliSls / $totalSls) * 100, 1) : 0;

        $deltaDetail    = $this->model->getDeltaSlsDetail();
        $deltaDetailSls = $this->model->getDeltaSlsDetailSls();
        $deltaKec       = []; // extract from $deltaDetail to avoid extra query
        foreach ($deltaDetail as $d) {
            $deltaKec[] = ['kdkec' => $d['kdkec'], 'nmkec' => $d['nmkec']];
        }
        $deltaTotalSls  = array_sum(array_column($deltaDetail, 'sls_extra'));

        $this->data['page_title'] = 'Insight & Analisa';
        $this->render('insight/index', [
            'summary'        => $summary,
            'anomali'        => $anomali,
            'beban'          => $beban,
            'distribusi'     => $distribusi,
            'coverage'       => $coverage,
            'rekomendasi'    => $rekomendasi,
            'user_pool'      => $userPool,
            'quality'        => $quality,
            'anomali_pct'    => $anomaliPct,
            'total_anomali'  => $totalAnomaliSls,
            'delta_detail'   => $deltaDetail,
            'delta_detail_sls' => $deltaDetailSls,
            'delta_kec'      => $deltaKec,
            'delta_total_sls' => $deltaTotalSls,
            'js'             => ['insight'],
        ]);
    }

    private function anomaliDetail(): void
    {
        $type  = $_GET['type'] ?? 'muatan_zero';
        $limit = min(200, max(10, (int) ($_GET['limit'] ?? 50)));
        $rows  = $this->model->getTopSlsAnomali('09', $limit, $type);
        $this->json(['success' => true, 'data' => $rows, 'type' => $type, 'total' => count($rows)]);
    }

    private function exportExcel(): void
    {
        $type = $_GET['type'] ?? 'anomali';
        $rows = match ($type) {
            'anomali'    => $this->model->getAnomaliPerKecamatan('09'),
            'beban'      => $this->model->getBebanKerjaPerKecamatan('09'),
            'coverage'   => $this->model->getCoverageGap('3509'),
            'distribusi' => $this->model->getDistribusiMuatan('09'),
            default      => [],
        };

        $filename = 'insight_' . $type . '_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        if (!empty($rows)) {
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $r) {
                fputcsv($out, $r);
            }
        }
        fclose($out);
        exit;
    }
}
