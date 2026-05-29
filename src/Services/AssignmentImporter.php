<?php

namespace App\Services;

use App\Core\Database;
use App\Helpers\AuditLog;

class AssignmentImporter
{
    private \PDO $pdo;

    private const HEADER_MAP = [
        'pcl'         => ['pcl', 'pencacah', 'username_pcl', 'pcl_username', 'petugas_pcl'],
        'pml'         => ['pml', 'pengawas', 'username_pml', 'pml_username', 'petugas_pml'],
        'task_force'  => ['task_force', 'tf', 'tugas_khusus', 'username_tf', 'tf_username'],
        'nmsls'       => ['nmsls', 'nama_sls', 'sls', 'nama_sls_rw', 'nama_blok_sensus'],
        'nmdesa'      => ['nmdesa', 'desa', 'nama_desa', 'desa_kelurahan'],
        'nmkec'      => ['nmkec', 'kecamatan', 'nama_kecamatan', 'kec'],
    ];

    public function __construct()
    {
        $this->pdo = Database::instance()->pdo();
    }

    public function preview(string $filePath): array
    {
        $reader = new \OpenSpout\Reader\XLSX\Reader();
        $reader->open($filePath);

        $headers = null;
        $mapping = [];
        $sample = [];
        $rowCount = 0;
        $errors = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $i => $row) {
                $cells = $row->toArray();
                if (empty(array_filter($cells, fn($c) => $c !== null && $c !== ''))) {
                    continue;
                }
                if ($headers === null) {
                    $headers = $cells;
                    $mapping = $this->mapHeaders($headers);
                    $missing = [];
                    foreach (['nmsls', 'nmdesa', 'nmkec'] as $req) {
                        if (!isset($mapping[$req])) $missing[] = $req;
                    }
                    if ($missing) {
                        $errors[] = 'Kolom wajib tidak ditemukan: ' . implode(', ', $missing);
                    }
                    continue;
                }
                $rowCount++;
                if (count($sample) < 5) {
                    $sample[] = $this->mapRow($cells, $mapping, $rowCount + 1);
                }
            }
            break;
        }
        $reader->close();

        return [
            'headers'    => $headers ?? [],
            'mapping'    => $mapping,
            'sample'     => $sample,
            'total_rows' => $rowCount,
            'errors'     => $errors,
            'has_pcl'    => isset($mapping['pcl']),
            'has_pml'    => isset($mapping['pml']),
            'has_tf'     => isset($mapping['task_force']),
        ];
    }

    public function import(string $filePath, int $userId, string $ipAddress): array
    {
        $reader = new \OpenSpout\Reader\XLSX\Reader();
        $reader->open($filePath);

        $headers = null;
        $mapping = [];
        $berhasil = 0;
        $gagal = 0;
        $errors = [];
        $batchId = date('Ymd') . '-' . bin2hex(random_bytes(8));

        try {
            $this->pdo->beginTransaction();

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $i => $row) {
                    $cells = $row->toArray();
                    if (empty(array_filter($cells, fn($c) => $c !== null && $c !== ''))) {
                        continue;
                    }
                    if ($headers === null) {
                        $headers = $cells;
                        $mapping = $this->mapHeaders($headers);
                        $missing = [];
                        foreach (['nmsls', 'nmdesa', 'nmkec'] as $req) {
                            if (!isset($mapping[$req])) $missing[] = $req;
                        }
                        if ($missing) {
                            throw new \RuntimeException('Kolom wajib tidak ditemukan: ' . implode(', ', $missing));
                        }
                        continue;
                    }

                    $data = $this->mapRow($cells, $mapping, $i);
                    if (empty($data['nmsls'])) {
                        $gagal++;
                        continue;
                    }

                    $sls = $this->findSls($data['nmsls'], $data['nmdesa'], $data['nmkec']);
                    if (!$sls) {
                        $gagal++;
                        $errors[] = "Baris {$i}: SLS tidak ditemukan: {$data['nmsls']} / {$data['nmdesa']} / {$data['nmkec']}";
                        continue;
                    }

                    $pclId = $data['pcl'] ? $this->findPetugas($data['pcl']) : null;
                    $pmlId = $data['pml'] ? $this->findPetugas($data['pml']) : null;
                    $tfId  = $data['task_force'] ? $this->findPetugas($data['task_force']) : null;

                    if ($data['pcl'] && !$pclId) {
                        $gagal++;
                        $errors[] = "Baris {$i}: PCL tidak ditemukan: {$data['pcl']}";
                        continue;
                    }
                    if ($data['pml'] && !$pmlId) {
                        $gagal++;
                        $errors[] = "Baris {$i}: PML tidak ditemukan: {$data['pml']}";
                        continue;
                    }
                    if ($data['task_force'] && !$tfId) {
                        $gagal++;
                        $errors[] = "Baris {$i}: Task Force tidak ditemukan: {$data['task_force']}";
                        continue;
                    }

                    $exists = $this->assignmentExists((int) $sls['id']);
                    if ($exists) {
                        $stmt = $this->pdo->prepare("UPDATE sipw_assignment SET pencacah_id = ?, pengawas_id = ?, task_force_id = ?, updated_at = NOW() WHERE sipw_id = ?");
                        $stmt->execute([$pclId, $pmlId, $tfId, (int) $sls['id']]);
                    } else {
                        $stmt = $this->pdo->prepare("INSERT INTO sipw_assignment (sipw_id, pencacah_id, pengawas_id, task_force_id, status) VALUES (?, ?, ?, ?, 'belum')");
                        $stmt->execute([(int) $sls['id'], $pclId, $pmlId, $tfId]);
                    }
                    $berhasil++;
                }
                break;
            }
            $reader->close();

            $this->pdo->commit();

            AuditLog::importEvent('complete', 'assignment_import_' . $batchId, [
                'berhasil' => $berhasil,
                'gagal'    => $gagal,
            ]);

        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $reader->close();
            throw $e;
        }

        return [
            'success'  => $gagal === 0,
            'batch_id' => $batchId,
            'stats'    => ['berhasil' => $berhasil, 'gagal' => $gagal],
            'errors'   => array_slice($errors, 0, 50),
        ];
    }

    private function mapHeaders(array $headers): array
    {
        $mapping = [];
        $normalized = array_map(fn($h) => $this->normalize((string) $h), $headers);

        foreach (self::HEADER_MAP as $field => $aliases) {
            foreach ($normalized as $idx => $n) {
                if (in_array($n, $aliases, true)) {
                    $mapping[$field] = $idx;
                    break;
                }
            }
        }
        return $mapping;
    }

    private function mapRow(array $cells, array $mapping, int $rowNum): array
    {
        $data = [];
        foreach (self::HEADER_MAP as $field => $aliases) {
            $idx = $mapping[$field] ?? null;
            $data[$field] = ($idx !== null && isset($cells[$idx])) ? trim((string) $cells[$idx]) : '';
        }
        $data['_row'] = $rowNum;
        return $data;
    }

    private function findSls(string $nmsls, string $nmdesa, string $nmkec): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, nmsls, nmdesa, nmkec FROM sipw_import WHERE nmsls = ? AND nmdesa = ? AND nmkec = ? LIMIT 1");
        $stmt->execute([$nmsls, $nmdesa, $nmkec]);
        return $stmt->fetch() ?: null;
    }

    private function findPetugas(string $username): ?int
    {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? AND status_akun = 'active' LIMIT 1");
        $stmt->execute([$username]);
        $r = $stmt->fetch();
        return $r ? (int) $r['id'] : null;
    }

    private function assignmentExists(int $sipwId): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM sipw_assignment WHERE sipw_id = ?");
        $stmt->execute([$sipwId]);
        return (bool) $stmt->fetch();
    }

    private function normalize(string $h): string
    {
        $h = trim($h);
        $h = mb_strtolower($h, 'UTF-8');
        $h = preg_replace('/[^a-z0-9_]/', '_', $h);
        $h = preg_replace('/_{2,}/', '_', $h);
        return trim($h, '_');
    }
}
