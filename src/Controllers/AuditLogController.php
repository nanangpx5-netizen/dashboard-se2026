<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AuditLogModel;

class AuditLogController extends Controller
{
    private AuditLogModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new AuditLogModel();
    }

    public function index(): void
    {
        if (($_GET['action'] ?? '') === 'data') {
            $this->dataTable();
            return;
        }

        $modules = $this->model->getModules();
        $users   = $this->model->getAuditedUsers();

        $this->data['page_title'] = 'Audit Log';
        $this->render('audit/index', [
            'modules'    => $modules,
            'users'      => $users,
            'actionLabel' => [AuditLogModel::class, 'actionLabel'],
            'js'         => ['audit'],
        ]);
    }

    private function dataTable(): void
    {
        try {
            $draw   = (int) ($_GET['draw'] ?? 0);
            $start  = max(0, (int) ($_GET['start'] ?? 0));
            $length = (int) ($_GET['length'] ?? 25);
            if ($length < 0) $length = 10000;

            $order = [];
            if (!empty($_GET['order'][0])) {
                $order = [
                    'column' => $_GET['order'][0]['column'],
                    'dir'    => $_GET['order'][0]['dir'],
                ];
            }

            $filters = [
                'module'    => $_GET['module'] ?? '',
                'user_id'   => $_GET['user_id'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to'   => $_GET['date_to'] ?? '',
                'search'    => $_GET['search']['value'] ?? '',
            ];

            $recordsTotal    = $this->model->totalCount();
            $recordsFiltered = $this->model->filteredCount($filters);
            $data            = $this->model->getDataTable($filters, $start, $length, $order);

            $rows = [];
            foreach ($data as $r) {
                $detailHtml = $this->formatDetail($r);
                $rows[] = [
                    'created_at'   => $r['created_at'],
                    'username'     => htmlspecialchars((string) ($r['username'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    'action'       => $r['action'] ?? '',
                    'action_label' => AuditLogModel::actionLabel($r['action'] ?? ''),
                    'module'       => $r['module'] ?? '',
                    'description'  => htmlspecialchars((string) ($r['description'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    'detail_html'  => $detailHtml,
                ];
            }

            $this->json([
                'draw'            => $draw,
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $rows,
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'draw'            => (int) ($_GET['draw'] ?? 0),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => $e->getMessage(),
            ]);
        }
    }

    private function formatDetail(array $r): string
    {
        $json = $r['detail_json'] ?? '';
        if (empty($json) || $json === 'null') return '';

        $data = json_decode($json, true);
        if (!is_array($data) || empty($data)) return '';

        // Petugas changes: render before/after table
        if (isset($data['before']) || isset($data['after'])) {
            $before = $data['before'] ?? [];
            $after  = $data['after'] ?? [];
            $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));

            $html = '<table class="table table-sm table-bordered mb-0 small" style="min-width:200px">';
            $html .= '<thead><tr><th>Kolom</th><th>Sebelum</th><th>Sesudah</th></tr></thead><tbody>';
            foreach ($allKeys as $k) {
                $old = htmlspecialchars(json_encode($before[$k] ?? '-', JSON_UNESCAPED_UNICODE));
                $new = htmlspecialchars(json_encode($after[$k] ?? '-', JSON_UNESCAPED_UNICODE));
                $html .= "<tr><td>{$k}</td><td>{$old}</td><td>{$new}</td></tr>";
            }
            return $html . '</tbody></table>';
        }

        // Import stats: render key-value
        if (isset($data['baris_berhasil']) || isset($data['batch_id'])) {
            $html = '<table class="table table-sm mb-0 small" style="min-width:200px"><tbody>';
            foreach ($data as $k => $v) {
                $val = is_scalar($v) ? htmlspecialchars((string) $v) : htmlspecialchars(json_encode($v, JSON_UNESCAPED_UNICODE));
                $html .= "<tr><td class='fw-semibold'>{$k}</td><td>{$val}</td></tr>";
            }
            return $html . '</tbody></table>';
        }

        // Assignment log: render old_data/new_data
        if (isset($data['old_data']) || isset($data['new_data'])) {
            $old = is_string($data['old_data'] ?? null) ? json_decode($data['old_data'], true) : ($data['old_data'] ?? []);
            $new = is_string($data['new_data'] ?? null) ? json_decode($data['new_data'], true) : ($data['new_data'] ?? []);
            $allKeys = array_unique(array_merge(
                is_array($old) ? array_keys($old) : [],
                is_array($new) ? array_keys($new) : [],
                ['sipw_id', 'assignment_id']
            ));

            $html = '<table class="table table-sm table-bordered mb-0 small" style="min-width:200px">';
            $html .= '<thead><tr><th>Kolom</th><th>Sebelum</th><th>Sesudah</th></tr></thead><tbody>';
            foreach ($allKeys as $k) {
                if ($k === 'sipw_id' || $k === 'assignment_id') {
                    $val = htmlspecialchars((string) ($data[$k] ?? '-'));
                    $html .= "<tr><td>{$k}</td><td colspan='2'>{$val}</td></tr>";
                    continue;
                }
                $oldVal = htmlspecialchars(json_encode($old[$k] ?? '-', JSON_UNESCAPED_UNICODE));
                $newVal = htmlspecialchars(json_encode($new[$k] ?? '-', JSON_UNESCAPED_UNICODE));
                $html .= "<tr><td>{$k}</td><td>{$oldVal}</td><td>{$newVal}</td></tr>";
            }
            return $html . '</tbody></table>';
        }

        return '<code class="small">' . htmlspecialchars($json) . '</code>';
    }
}
