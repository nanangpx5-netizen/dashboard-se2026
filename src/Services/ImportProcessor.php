<?php

namespace App\Services;

use App\Core\Database;
use App\Helpers\Backup;
use App\Helpers\Cache;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * ImportProcessor — Proses import Excel SIPW secara streaming & memory-efficient
 *
 * Fitur:
 * - Streaming reader (tidak load semua row ke memory)
 * - Batch insert/update 500 rows per transaction chunk
 * - UPSERT (ON DUPLICATE KEY UPDATE) untuk update data duplikat
 * - Progress tracking via callback
 * - Auto-rollback jika gagal
 * - Logging ke dash_import_log
 */
class ImportProcessor
{
    /** Jumlah baris per batch transaksi */
    public const BATCH_SIZE = 500;

    /** Path penyimpanan file upload sementara */
    private string $storagePath;

    private ImportValidator $validator;
    private \PDO $pdo;

    /** Status import */
    private string $batchId;
    private string $status = 'processing';
    private int $totalBaris = 0;
    private int $barisBerhasil = 0;
    private int $barisDiupdate = 0;
    private int $barisGagal = 0;
    private array $errorMessages = [];

    public function __construct()
    {
        $this->validator = new ImportValidator();
        $this->pdo = Database::instance()->pdo();
        $this->storagePath = dirname(__DIR__, 2) . '/storage/import';

        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    /**
     * Parse Excel file dan return header info + sample rows untuk preview
     *
     * @param string $filePath Path ke file Excel
     * @param int    $sampleRows Jumlah sample rows (default 50)
     * @return array ['headers' => [], 'mapping' => [], 'sample' => [], 'total_rows' => int, 'validation' => []]
     */
    public function preview(string $filePath, int $sampleRows = 50): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $reader = $this->createReader($ext);
        $reader->open($filePath);

        $headers = null;
        $sample = [];
        $rowCount = 0;
        $headerValidation = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->toArray();

                if (empty(array_filter($cells, fn($c) => $c !== null && $c !== ''))) {
                    continue;
                }

                if ($headers === null) {
                    $headers = $cells;
                    $headerValidation = $this->validator->validateHeaders($headers);
                    continue;
                }

                $rowCount++;

                if (count($sample) < $sampleRows) {
                    $sample[] = $this->validator->validateRow(
                        $cells,
                        $headerValidation['mapping'],
                        $rowCount + 1
                    );
                }
            }
            break; // Hanya sheet pertama
        }

        $reader->close();

        return [
            'headers'           => $headers ?? [],
            'header_validation' => $headerValidation,
            'sample'            => $sample,
            'total_rows'        => $rowCount,
            'file_path'         => $filePath,
            'ext'               => $ext,
        ];
    }

    /**
     * Preview page tertentu dari file (untuk AJAX pagination)
     *
     * @param string $filePath
     * @param int    $page     Halaman (1-based)
     * @param int    $perPage  Baris per halaman
     * @return array
     */
    public function previewPage(string $filePath, int $page = 1, int $perPage = 50): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $reader = $this->createReader($ext);
        $reader->open($filePath);

        $headers = null;
        $mapping = [];
        $rows = [];
        $rowIndex = 0;
        $start = ($page - 1) * $perPage;
        $end = $start + $perPage;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->toArray();
                if (empty(array_filter($cells, fn($c) => $c !== null && $c !== ''))) {
                    continue;
                }

                if ($headers === null) {
                    $headers = $cells;
                    $headerValidation = $this->validator->validateHeaders($headers);
                    $mapping = $headerValidation['mapping'];
                    continue;
                }

                $rowIndex++;

                if ($rowIndex > $start && $rowIndex <= $end) {
                    $result = $this->validator->validateRow($cells, $mapping, $rowIndex + 1);
                    $result['row_number'] = $rowIndex + 1;
                    $rows[] = $result;
                }

                if ($rowIndex > $end) {
                    break;
                }
            }
            break;
        }

        $reader->close();

        return [
            'rows'  => $rows,
            'page'  => $page,
            'start' => $start + 1,
            'end'   => min($end, $rowIndex),
        ];
    }

    /**
     * Import file Excel ke sipw_import dengan batch + UPSERT
     *
     * @param string $filePath   Path ke file Excel
     * @param int    $userId     ID user yang melakukan import
     * @param string $ipAddress  IP address user
     * @param callable|null $progressCallback function($processed, $total, $inserted, $updated, $failed)
     * @return array  ['success' => bool, 'batch_id' => string, 'stats' => [], 'errors' => []]
     */
    public function import(string $filePath, int $userId, string $ipAddress, ?callable $progressCallback = null): array
    {
        $this->batchId = $this->generateBatchId();
        $waktuMulai = date('Y-m-d H:i:s');

        // Simpan snapshot data sebelum import untuk rollback
        $this->saveRollbackPoint($userId, $ipAddress);

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $reader = $this->createReader($ext);
        $reader->open($filePath);

        $headers = null;
        $mapping = [];
        $rowsProcessed = 0;
        $batchRows = [];
        $previewInfo = $this->preview($filePath, 1);
        $this->totalBaris = $previewInfo['total_rows'];
        $headerValidation = [];

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $cells = $row->toArray();
                    if (empty(array_filter($cells, fn($c) => $c !== null && $c !== ''))) {
                        continue;
                    }

                    if ($headers === null) {
                        $headers = $cells;
                        $headerValidation = $this->validator->validateHeaders($headers);

                        if (!empty($headerValidation['missing_required'])) {
                            throw new \RuntimeException(
                                'Kolom wajib tidak ditemukan: ' . implode(', ', $headerValidation['missing_required'])
                            );
                        }
                        $mapping = $headerValidation['mapping'];
                        continue;
                    }

                    $rowsProcessed++;
                    $result = $this->validator->validateRow($cells, $mapping, $rowsProcessed + 1);

                    if ($result['valid']) {
                        $batchRows[] = $result['row'];
                    } else {
                        $this->barisGagal++;
                        $this->errorMessages = array_merge($this->errorMessages, $result['errors']);
                    }

                    // Process batch
                    if (count($batchRows) >= self::BATCH_SIZE) {
                        $this->processBatch($batchRows);
                        $batchRows = [];
                        gc_collect_cycles();

                        if ($progressCallback) {
                            $progressCallback($rowsProcessed, $this->totalBaris, $this->barisBerhasil, $this->barisDiupdate, $this->barisGagal);
                        }
                    }
                }
                break;
            }

            $reader->close();

            // Proses sisa batch
            if (!empty($batchRows)) {
                $this->processBatch($batchRows);
                $batchRows = [];
            }

            // Update status
            $waktuSelesai = date('Y-m-d H:i:s');
            if ($this->barisGagal > 0 && $this->barisBerhasil > 0) {
                $this->status = 'partial';
            } elseif ($this->barisGagal > 0 && $this->barisBerhasil === 0) {
                $this->status = 'failed';
            } else {
                $this->status = 'success';
            }

            $this->logImport($userId, $ipAddress, $waktuMulai, $waktuSelesai);

            // Hapus cache agar data fresh
            Cache::forget('kecamatan_list');
            Cache::forget('dashboard_stats');
            Cache::forget('dashboard_wilayah');
            Cache::forget('dashboard_beban');

            return [
                'success'  => $this->status !== 'failed',
                'batch_id' => $this->batchId,
                'stats'    => [
                    'total_baris'    => $this->totalBaris,
                    'baris_berhasil' => $this->barisBerhasil,
                    'baris_diupdate' => $this->barisDiupdate,
                    'baris_gagal'    => $this->barisGagal,
                ],
                'errors'   => array_slice($this->errorMessages, 0, 100), // Max 100 error
                'status'   => $this->status,
            ];

        } catch (\Throwable $e) {
            $reader->close();

            $waktuSelesai = date('Y-m-d H:i:s');
            $this->status = 'failed';
            $this->errorMessages[] = $e->getMessage();

            $this->logImport($userId, $ipAddress, $waktuMulai, $waktuSelesai);

            return [
                'success'  => false,
                'batch_id' => $this->batchId,
                'stats'    => [
                    'total_baris'    => $this->totalBaris,
                    'baris_berhasil' => $this->barisBerhasil,
                    'baris_diupdate' => $this->barisDiupdate,
                    'baris_gagal'    => $this->totalBaris,
                ],
                'errors'   => [$e->getMessage()],
                'status'   => 'failed',
            ];
        }
    }

    /**
     * Proses batch rows ke database dengan transaksi
     */
    private function processBatch(array &$rows): void
    {
        if (empty($rows)) return;

        $sql = "INSERT INTO sipw_import
                (idfrs, semester, idsubsls,
                 kdprov, kdkab, kdkec, kddesa, kdsls,
                 nmprov, nmkab, nmkec, nmdesa,
                 nmsls, nama_ketua,
                 kk, btt, bttk, bku,
                 bbtt_nonusaha, usaha, muatan)
                VALUES (?,?,?,
                        ?,?,?,?,?,
                        ?,?,?,?,
                        ?,?,
                        ?,?,?,?,
                        ?,?,?)
                ON DUPLICATE KEY UPDATE
                    semester      = VALUES(semester),
                    idsubsls      = VALUES(idsubsls),
                    kdprov        = VALUES(kdprov),
                    kdkab         = VALUES(kdkab),
                    kdkec         = VALUES(kdkec),
                    kddesa        = VALUES(kddesa),
                    kdsls         = VALUES(kdsls),
                    nmprov        = VALUES(nmprov),
                    nmkab         = VALUES(nmkab),
                    nmkec         = VALUES(nmkec),
                    nmdesa        = VALUES(nmdesa),
                    nmsls         = VALUES(nmsls),
                    nama_ketua    = VALUES(nama_ketua),
                    kk            = VALUES(kk),
                    btt           = VALUES(btt),
                    bttk          = VALUES(bttk),
                    bku           = VALUES(bku),
                    bbtt_nonusaha = VALUES(bbtt_nonusaha),
                    usaha         = VALUES(usaha),
                    muatan        = VALUES(muatan),
                    updated_at    = NOW()";

        $stmt = $this->pdo->prepare($sql);

        try {
            $this->pdo->beginTransaction();

            foreach ($rows as $row) {
                $stmt->execute([
                    $row['idfrs'] ?? null,
                    $row['semester'] ?? null,
                    $row['idsubsls'] ?? null,
                    $row['kdprov'] ?? null,
                    $row['kdkab'] ?? null,
                    $row['kdkec'] ?? null,
                    $row['kddesa'] ?? null,
                    $row['kdsls'] ?? null,
                    $row['nmprov'] ?? null,
                    $row['nmkab'] ?? null,
                    $row['nmkec'] ?? null,
                    $row['nmdesa'] ?? null,
                    $row['nmsls'] ?? null,
                    $row['nama_ketua'] ?? null,
                    (int) ($row['kk'] ?? 0),
                    (int) ($row['btt'] ?? 0),
                    (int) ($row['bttk'] ?? 0),
                    (int) ($row['bku'] ?? 0),
                    (int) ($row['bbtt_nonusaha'] ?? 0),
                    (int) ($row['usaha'] ?? 0),
                    (int) ($row['muatan'] ?? 0),
                ]);

                if ($stmt->rowCount() > 0) {
                    if ($stmt->rowCount() === 1) {
                        $this->barisBerhasil++;
                    } else {
                        // rowCount = 2 for UPDATE on DUPLICATE KEY
                        $this->barisDiupdate++;
                    }
                }
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $this->barisGagal += count($rows);
            $this->errorMessages[] = 'Batch error: ' . $e->getMessage();
        }
    }

    /**
     * Simpan log import ke dash_import_log
     */
    private function logImport(int $userId, string $ipAddress, string $waktuMulai, string $waktuSelesai): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO dash_import_log
                (batch_id, nama_file, ukuran_file, total_baris,
                 baris_berhasil, baris_diupdate, baris_gagal,
                 status, user_id, waktu_mulai, waktu_selesai,
                 error_message, ip_address)
            VALUES (?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?)
        ");

        $stmt->execute([
            $this->batchId,
            $this->namaFile ?? 'unknown',
            0, // ukuran file — tidak disimpan di processor
            $this->totalBaris,
            $this->barisBerhasil,
            $this->barisDiupdate,
            $this->barisGagal,
            $this->status,
            $userId,
            $waktuMulai,
            $waktuSelesai,
            !empty($this->errorMessages) ? implode("\n", array_slice($this->errorMessages, 0, 50)) : null,
            $ipAddress,
        ]);
    }

    public function setNamaFile(string $namaFile): void
    {
        $this->namaFile = $namaFile;
    }

    public function setUkuranFile(int $ukuran): void
    {
        $this->ukuranFile = $ukuran;
    }

    /**
     * Simpan uploaded file ke storage dan return path
     */
    public function storeUploadedFile(array $file): string
    {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'import_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destPath = $this->storagePath . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new \RuntimeException('Gagal menyimpan file upload');
        }

        return $destPath;
    }

    /**
     * Hapus file dari storage
     */
    public function cleanupFile(string $filePath): void
    {
        if (is_file($filePath) && str_starts_with(realpath($filePath), realpath($this->storagePath))) {
            unlink($filePath);
        }
    }

    /**
     * Generate unique batch ID
     */
    private function generateBatchId(): string
    {
        return date('Ymd') . '-' . bin2hex(random_bytes(8));
    }

    /**
     * Buat reader sesuai ekstensi file
     */
    private function createReader(string $ext): CsvReader|XlsxReader
    {
        return match ($ext) {
            'csv'  => new CsvReader(),
            'xls'  => new XlsxReader(),
            default => new XlsxReader(),
        };
    }

    /**
     * Get daftar log import terbaru
     */
    public function getImportHistory(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                l.*,
                u.username AS user_name
            FROM dash_import_log l
            LEFT JOIN users u ON u.id = l.user_id
            ORDER BY l.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get statistik import
     */
    public function getImportStats(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                COUNT(*) AS total_import,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS success,
                SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) AS partial,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed,
                COALESCE(SUM(baris_berhasil), 0) AS total_berhasil,
                COALESCE(SUM(baris_diupdate), 0) AS total_diupdate,
                COALESCE(SUM(baris_gagal), 0) AS total_gagal
            FROM dash_import_log
        ");
        return $stmt->fetch();
    }

    /**
     * Simpan snapshot data sipw_import sebelum import batch.
     * Rollback point dipakai oleh Backup::rollbackImport().
     */
    private function saveRollbackPoint(int $userId, string $ipAddress): void
    {
        try {
            // Ambil semua ID yang sudah ada
            $stmt = $this->pdo->query("SELECT id FROM sipw_import");
            $existingIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            if (empty($existingIds)) {
                return; // Tabel kosong — tidak perlu snapshot
            }

            // Simpan old_data via helper (akan mengambil snapshot lengkap)
            Backup::createRollbackPoint(
                $this->batchId,
                $existingIds,
                'Pre-import snapshot: ' . basename($this->namaFile ?? 'unknown')
            );
        } catch (\Throwable $e) {
            // Jangan gagalkan import hanya karena gagal snapshot
            error_log('Warning: Gagal membuat rollback point: ' . $e->getMessage());
        }
    }
}
