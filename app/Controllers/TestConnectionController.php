<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Config\Database as DbConfig;

class TestConnectionController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $pageTitle = 'Validasi Shared Database — Dashboard SE2026';
        $testResults = [];
        $errors = [];
        $success = false;
        $config = DbConfig::all();
        $configSafe = array_merge($config, ['password' => '********']);

        try {
            $testResults['proof_database']     = $this->proofDatabaseName();
            $testResults['proof_connection']   = $this->proofConnectionId();
            $testResults['proof_server']       = $this->proofServerInfo();
            $testResults['live_counts']        = $this->liveTableCounts();
            $testResults['sample_users']       = $this->sampleData('users', 10);
            $testResults['sample_wilayah']     = $this->sampleData('wilayah_kerja', 10);
            $testResults['sample_desa']        = $this->sampleDesaData();
            $testResults['realtime_proof']     = $this->realtimeValidation();
            $testResults['all_tables']         = $this->allTableCounts();
            $success = true;
        } catch (\Throwable $e) {
            $errors[] = [
                'type'    => 'VALIDATION_ERROR',
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ];
            Database::logError('VALIDATION_ERROR', $e->getMessage(), $e->getFile(), $e->getLine());
        }

        $system = [
            'php_version'      => PHP_VERSION,
            'server_software'  => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
            'server_time'      => date('Y-m-d H:i:s'),
            'server_timezone'  => date_default_timezone_get(),
            'loaded_extensions' => implode(', ', array_intersect(
                get_loaded_extensions(),
                ['pdo_mysql', 'mbstring', 'json', 'fileinfo', 'gd', 'openssl', 'zip']
            )),
        ];

        require VIEW_PATH . '/layouts/main.php';
    }

    // ────────────────────────────────────────────────────────────
    //  1. SHARED DATABASE PROOF — SELECT DATABASE()
    // ────────────────────────────────────────────────────────────
    private function proofDatabaseName(): array
    {
        $dbName = $this->db->getCurrentDatabase();
        $expected = 'bps_jember_se2026';
        $isValid = ($dbName === $expected);

        return [
            'status'      => $isValid ? 'PASS' : 'FAIL',
            'query'       => 'SELECT DATABASE()',
            'result'      => $dbName,
            'expected'    => $expected,
            'is_valid'    => $isValid,
            'description' => $isValid
                ? 'Dashboard membaca database ASLI: ' . $dbName
                : 'HARUSNYA: ' . $expected . ', tetapi mendapat: ' . $dbName,
        ];
    }

    // ────────────────────────────────────────────────────────────
    //  2. PDO SINGLETON PROOF — SELECT CONNECTION_ID()
    // ────────────────────────────────────────────────────────────
    private function proofConnectionId(): array
    {
        $connId = $this->db->getConnectionId();

        return [
            'status'       => 'PASS',
            'query'        => 'SELECT CONNECTION_ID()',
            'connection_id' => $connId,
            'description'  => 'Koneksi PDO singleton aktif (ID: ' . $connId . ')',
        ];
    }

    // ────────────────────────────────────────────────────────────
    //  3. SERVER INFO
    // ────────────────────────────────────────────────────────────
    private function proofServerInfo(): array
    {
        return [
            'status'        => 'PASS',
            'version'       => $this->db->serverInfo(),
            'server_time'   => $this->db->serverTime(),
            'timezone'      => $this->db->sessionTimezone(),
            'active_connections' => $this->db->activeConnectionCount(),
        ];
    }

    // ────────────────────────────────────────────────────────────
    //  4. LIVE TABLE COUNTS (real-time, no cache)
    // ────────────────────────────────────────────────────────────
    private function liveTableCounts(): array
    {
        $tablesToCount = [
            'users'           => 'users',
            'alokasi_petugas' => 'alokasi_petugas',
            'wilayah_kerja'   => 'wilayah_kerja',
        ];

        // Also try desa — may not exist in this schema
        if ($this->db->tableExists('desa')) {
            $tablesToCount['desa'] = 'desa';
        }

        $results = [];
        foreach ($tablesToCount as $label => $table) {
            $exists = $this->db->tableExists($table);
            $results[$label] = [
                'table_name' => $table,
                'exists'     => $exists,
                'count'      => $exists ? $this->db->count($table) : null,
            ];
        }

        return $results;
    }

    // ────────────────────────────────────────────────────────────
    //  5. LIVE SAMPLE DATA (direct from database, no cache)
    // ────────────────────────────────────────────────────────────
    private function sampleData(string $table, int $limit = 10): array
    {
        $exists = $this->db->tableExists($table);
        if (!$exists) {
            return [
                'status'  => 'SKIP',
                'table'   => $table,
                'message' => 'Tabel tidak ditemukan di database',
                'rows'    => [],
                'columns' => [],
            ];
        }

        $columns = $this->db->getTableColumns($table);
        $colNames = array_map(fn($c) => $c['COLUMN_NAME'], $columns);

        // Exclude sensitive columns
        $safeCols = array_filter($colNames, fn($col) => !in_array($col, ['password', 'token', 'secret']));
        $safeColList = implode(', ', $safeCols);

        $rows = $this->db->fetchAll(
            "SELECT {$safeColList} FROM {$table} LIMIT ?",
            [$limit]
        );

        return [
            'status'      => count($rows) > 0 ? 'PASS' : 'EMPTY',
            'table'       => $table,
            'row_count'   => count($rows),
            'columns'     => array_values($safeCols),
            'rows'        => $rows,
        ];
    }

    // ────────────────────────────────────────────────────────────
    //  5b. SPECIAL HANDLING: desa (via wilayah_kerja)
    // ────────────────────────────────────────────────────────────
    private function sampleDesaData(): array
    {
        // Check if dedicated 'desa' table exists
        if ($this->db->tableExists('desa')) {
            return $this->sampleData('desa', 10);
        }

        // Otherwise, desa/kelurahan data is in wilayah_kerja
        // Try to find desa-level records using kode_wilkerstat length
        $exists = $this->db->tableExists('wilayah_kerja');
        if (!$exists) {
            return [
                'status'  => 'SKIP',
                'table'   => 'desa',
                'message' => 'Tabel desa tidak ditemukan. Data desa dikelola melalui tabel wilayah_kerja di web SE2026.',
                'rows'    => [],
                'columns' => [],
            ];
        }

        // Check if wilayah_kerja has desa-level records
        $desaCount = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM wilayah_kerja WHERE LENGTH(kode_kecamatan) > 10"
        );

        if ($desaCount > 0) {
            $rows = $this->db->fetchAll(
                "SELECT * FROM wilayah_kerja WHERE LENGTH(kode_kecamatan) > 10 LIMIT 10"
            );
            $columns = $this->db->getTableColumns('wilayah_kerja');
            $colNames = array_map(fn($c) => $c['COLUMN_NAME'], $columns);

            return [
                'status'       => 'PASS',
                'table'        => 'wilayah_kerja (desa level)',
                'row_count'    => count($rows),
                'columns'      => $colNames,
                'rows'         => $rows,
                'note'         => 'Data desa disimpan dalam tabel wilayah_kerja dengan kode > 10 digit',
            ];
        }

        return [
            'status'  => 'EMPTY',
            'table'   => 'desa',
            'message' => 'Tabel desa tidak tersedia. Web SE2026 mengelola data desa melalui tabel wilayah_kerja.',
            'rows'    => [],
            'columns' => [],
        ];
    }

    // ────────────────────────────────────────────────────────────
    //  6. REALTIME VALIDATION — PROOF MECHANISM
    // ────────────────────────────────────────────────────────────
    private function realtimeValidation(): array
    {
        $now = $this->db->serverTime();
        $dbName = $this->db->getCurrentDatabase();
        $connId = $this->db->getConnectionId();
        $counts = [];

        $keyTables = ['users', 'wilayah_kerja', 'alokasi_petugas', 'monitoring_progress', 'sipw_import', 'sipw_assignment', 'activity_logs', 'surat_tugas'];
        foreach ($keyTables as $tbl) {
            $exists = $this->db->tableExists($tbl);
            $counts[$tbl] = $exists ? $this->db->count($tbl) : -1;
        }

        return [
            'status'              => 'VALID',
            'database'            => $dbName,
            'connection_id'       => $connId,
            'server_time'         => $now,
            'table_counts'        => $counts,
            'total_queries'       => $this->db->getQueryCount(),
            'message'             => 'Dashboard membaca REAL-TIME dari database ASLI ' . $dbName,
            'how_to_prove'        => [
                '1. Buka web SE2026 (aplikasi existing)',
                '2. Tambah atau edit data (contoh: tambah user baru di tabel users)',
                '3. Refresh halaman ini: /dashboard-se2026/test-connection',
                '4. Lihat kolom "count" — harus berubah tanpa proses sinkronisasi',
                '5. Jika berubah langsung → VALID: shared realtime database',
            ],
        ];
    }

    // ────────────────────────────────────────────────────────────
    //  7. ALL TABLE COUNTS
    // ────────────────────────────────────────────────────────────
    private function allTableCounts(): array
    {
        $tableNames = $this->db->fetchAll(
            "SELECT TABLE_NAME FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'
             ORDER BY TABLE_NAME",
            [$this->db->getCurrentDatabase()]
        );

        $result = [];
        foreach ($tableNames as $row) {
            $tbl = $row['TABLE_NAME'];
            $result[] = [
                'name'  => $tbl,
                'count' => $this->db->count($tbl),
            ];
        }

        return $result;
    }
}
