<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Helpers\AuditLog;
use App\Helpers\Session;
use App\Services\ImportProcessor;
use App\Services\ImportValidator;

/**
 * ImportController — Modul Import SIPW
 *
 * Flow:
 *   1. GET  → tampilkan form upload + riwayat import
 *   2. POST action=upload     → upload file, parse, validasi header, preview
 *   3. GET  action=preview    → AJAX paginated preview dari stored file
 *   4. POST action=import     → proses import batch + UPSERT + log
 *   5. POST action=cancel     → batal import, hapus file
 *
 * Keamanan:
 *   - Hanya admin/operator (via RoleMiddleware di index.php)
 *   - CSRF validation via middleware
 *   - Validasi ekstensi file
 *   - Validasi header & baris
 */
class ImportController extends Controller
{
    private const MAX_FILE_SIZE  = 20 * 1024 * 1024; // 20 MB
    private const ALLOWED_EXT    = ['xlsx', 'xls', 'csv'];
    private const PREVIEW_PER_PAGE = 50;

    private ImportProcessor $processor;

    public function __construct()
    {
        parent::__construct();
        $this->processor = new ImportProcessor();
    }

    /**
     * Halaman utama import — upload form + history
     */
    public function index(): void
    {
        $action = $_GET['action'] ?? '';

        // ─── POST handlers ───────────────────────────────────────────────
        if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleUpload();
            return;
        }

        if ($action === 'import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleImport();
            return;
        }

        if ($action === 'cancel' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCancel();
            return;
        }

        // ─── GET AJAX preview page ──────────────────────────────────────
        if ($action === 'ajax-preview' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->handleAjaxPreview();
            return;
        }

        // ─── Tampilkan halaman ──────────────────────────────────────────
        $pdo = Database::instance()->pdo();

        // Data existing — single query
        $stats = $pdo->query("
            SELECT
                COUNT(*)                    AS total_sls,
                COALESCE(SUM(muatan), 0)    AS total_muatan,
                COUNT(DISTINCT kdkec)       AS total_kec,
                COUNT(DISTINCT CONCAT(kdkec, kddesa)) AS total_desa
            FROM sipw_import
        ")->fetch();
        $totalSls   = $stats['total_sls'];
        $totalMuatan = $stats['total_muatan'];
        $totalKec    = $stats['total_kec'];
        $totalDesa   = $stats['total_desa'];

        // Import history
        $history = $this->processor->getImportHistory(20);
        $stats   = $this->processor->getImportStats();

        // Session data untuk preview
        $sessionFile = Session::get('import_file');
        $previewInfo = null;

        if ($sessionFile && is_file($sessionFile)) {
            // Baca info preview yang tersimpan di session
            $previewInfo = Session::get('import_preview');
        }

        $this->data['page_title'] = 'Import SIPW';
        $this->render('import/index', [
            'total_sls'    => $totalSls,
            'total_muatan' => $totalMuatan,
            'total_kec'    => $totalKec,
            'total_desa'   => $totalDesa,
            'history'      => $history,
            'stats'        => $stats,
            'preview_info' => $previewInfo,
            'has_file'     => $sessionFile && is_file($sessionFile),
            'js'           => ['import'],
        ]);
    }

    /**
     * Upload file + parse header + preview
     */
    private function handleUpload(): void
    {
        $file = $_FILES['import_file'] ?? null;

        // ─── Validasi upload ──────────────────────────────────────────
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $errCodes = [
                UPLOAD_ERR_INI_SIZE   => 'Ukuran file melebihi batas server (post_max_size).',
                UPLOAD_ERR_FORM_SIZE  => 'Ukuran file melebihi batas form.',
                UPLOAD_ERR_PARTIAL    => 'File hanya terupload sebagian. Coba upload ulang.',
                UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang dipilih.',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary server tidak ditemukan.',
            ];
            $msg = $errCodes[$file['error'] ?? UPLOAD_ERR_NO_FILE] ?? 'Terjadi kesalahan saat upload.';
            Session::flash('error', $msg);
            $this->redirect('?page=dashboard&sub=import');
            return;
        }

        // ─── Validasi ukuran ───────────────────────────────────────────
        if ($file['size'] > self::MAX_FILE_SIZE) {
            Session::flash('error', 'Ukuran file maksimal 20 MB.');
            $this->redirect('?page=dashboard&sub=import');
            return;
        }

        // ─── Validasi ekstensi ─────────────────────────────────────────
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            Session::flash('error', 'Format file harus: XLSX, XLS, atau CSV.');
            $this->redirect('?page=dashboard&sub=import');
            return;
        }

        // ─── Simpan file ───────────────────────────────────────────────
        try {
            $storedPath = $this->processor->storeUploadedFile($file);
        } catch (\Throwable $e) {
            Session::flash('error', 'Gagal menyimpan file: ' . $e->getMessage());
            $this->redirect('?page=dashboard&sub=import');
            return;
        }

        // ─── Parse preview ─────────────────────────────────────────────
        try {
            $preview = $this->processor->preview($storedPath, self::PREVIEW_PER_PAGE);
        } catch (\Throwable $e) {
            $this->processor->cleanupFile($storedPath);
            Session::flash('error', 'Gagal membaca file: ' . $e->getMessage());
            $this->redirect('?page=dashboard&sub=import');
            return;
        }

        // ─── Validasi header ──────────────────────────────────────────
        $headerVal = $preview['header_validation'] ?? [];

        if (!empty($headerVal['missing_required'])) {
            $this->processor->cleanupFile($storedPath);
            $msg = 'Kolom wajib tidak ditemukan: <strong>' .
                   implode(', ', $headerVal['missing_required']) . '</strong>.' .
                   ' Pastikan file berisi kolom kode kecamatan dan kode desa.';
            Session::flash('error', $msg);
            $this->redirect('?page=dashboard&sub=import');
            return;
        }

        if (empty($preview['total_rows'])) {
            $this->processor->cleanupFile($storedPath);
            Session::flash('error', 'File tidak mengandung data. Pastikan file tidak kosong.');
            $this->redirect('?page=dashboard&sub=import');
            return;
        }

        // ─── Simpan info preview di session ────────────────────────────
        Session::set('import_file', $storedPath);
        Session::set('import_preview', [
            'nama_file'           => $file['name'],
            'ukuran_file'         => $file['size'],
            'total_baris'         => $preview['total_rows'],
            'total_sample'        => count($preview['sample']),
            'headers'             => $preview['headers'],
            'header_validation'   => $headerVal,
            'missing_required'    => $headerVal['missing_required'] ?? [],
            'missing_recommended' => $headerVal['missing_recommended'] ?? [],
            'unmapped_headers'    => $headerVal['unmapped'] ?? [],
            'mapping'             => $headerVal['mapping'] ?? [],
            'waktu_upload'        => date('Y-m-d H:i:s'),
        ]);

        Session::flash('success', "File berhasil diupload. {$preview['total_rows']} baris data ditemukan.");
        $this->redirect('?page=dashboard&sub=import');
    }

    /**
     * Proses import ke database
     */
    private function handleImport(): void
    {
        $filePath = Session::get('import_file');
        $preview  = Session::get('import_preview');

        if (!$filePath || !is_file($filePath) || !$preview) {
            Session::flash('error', 'Data preview tidak ditemukan. Silakan upload ulang.');
            $this->redirect('?page=dashboard&sub=import');
            return;
        }

        $user = Session::get('user');
        $userId = (int) ($user['id'] ?? 0);
        $ipAddr = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $namaFile = $preview['nama_file'] ?? 'unknown';

        AuditLog::importEvent('start', $namaFile, [
            'total_baris' => $preview['total_baris'] ?? 0,
        ]);

        $this->processor->setNamaFile($namaFile);
        $this->processor->setUkuranFile($preview['ukuran_file'] ?? 0);

        // Proses import
        $result = $this->processor->import($filePath, $userId, $ipAddr);

        // Hapus file setelah import
        $this->processor->cleanupFile($filePath);

        // Bersihkan session
        Session::remove('import_file');
        Session::remove('import_preview');

        // Flash message
        if ($result['success']) {
            AuditLog::importEvent('complete', $namaFile, [
                'batch_id' => $result['batch_id'],
                'baris_berhasil' => $result['stats']['baris_berhasil'],
                'baris_diupdate' => $result['stats']['baris_diupdate'],
                'baris_gagal' => $result['stats']['baris_gagal'],
            ]);
            $msg = 'Import berhasil! ' .
                   number_format($result['stats']['baris_berhasil']) . ' baru, ' .
                   number_format($result['stats']['baris_diupdate']) . ' diupdate' .
                   ($result['stats']['baris_gagal'] > 0 ? ', ' . number_format($result['stats']['baris_gagal']) . ' gagal' : '') .
                   ' (Batch: ' . $result['batch_id'] . ').';
            Session::flash(
                $result['stats']['baris_gagal'] > 0 ? 'warning' : 'success',
                $msg
            );
        } else {
            AuditLog::importEvent('failed', $namaFile, [
                'error' => $result['errors'][0] ?? 'Unknown error',
            ]);
            $msg = 'Import gagal: ' . ($result['errors'][0] ?? 'Unknown error');
            Session::flash('error', $msg);
        }

        // Simpan batch_id untuk detail
        if ($result['batch_id']) {
            Session::flash('batch_id', $result['batch_id']);
        }

        $this->redirect('?page=dashboard&sub=import');
    }

    /**
     * Batal import — hapus file + session
     */
    private function handleCancel(): void
    {
        $filePath = Session::get('import_file');
        if ($filePath) {
            $this->processor->cleanupFile($filePath);
        }

        Session::remove('import_file');
        Session::remove('import_preview');
        AuditLog::importEvent('cancelled', basename($filePath ?? 'unknown'));
        Session::flash('info', 'Import dibatalkan.');

        $this->redirect('?page=dashboard&sub=import');
    }

    /**
     * AJAX: preview page dari stored file
     */
    private function handleAjaxPreview(): void
    {
        $filePath = Session::get('import_file');

        if (!$filePath || !is_file($filePath)) {
            $this->json([
                'success' => false,
                'message' => 'File tidak ditemukan. Silakan upload ulang.',
            ]);
            return;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($_GET['per_page'] ?? self::PREVIEW_PER_PAGE)));

        try {
            $data = $this->processor->previewPage($filePath, $page, $perPage);
            $this->json([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
