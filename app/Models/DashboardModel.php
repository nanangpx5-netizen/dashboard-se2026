<?php

namespace App\Models;

class DashboardModel extends BaseModel
{
    public function getSystemSummary(): array
    {
        return [
            'database'   => $this->databaseName(),
            'server'     => $this->db->serverInfo(),
            'connected'  => $this->db->isConnected(),
            'timestamp'  => $this->fetchColumn("SELECT NOW()"),
            'timezone'   => $this->fetchColumn("SELECT @@session.time_zone"),
        ];
    }

    public function getTableCounts(): array
    {
        $tables = [
            'users'               => 'users',
            'wilayah_kerja'       => 'wilayah_kerja',
            'alokasi_petugas'     => 'alokasi_petugas',
            'monitoring_progress' => 'monitoring_progress',
            'sipw_import'         => 'sipw_import',
            'sipw_assignment'     => 'sipw_assignment',
            'activity_logs'       => 'activity_logs',
            'surat_tugas'         => 'surat_tugas',
        ];

        $result = [];

        foreach ($tables as $label => $table) {
            $exists = $this->tableExists($table);
            $result[$label] = [
                'table_name' => $table,
                'exists'     => $exists,
                'count'      => $exists ? $this->count($table) : null,
                'columns'    => $exists ? count($this->getTableColumns($table)) : null,
            ];
        }

        return $result;
    }

    public function getForeignKeyMap(): array
    {
        return $this->fetchAll(
            "SELECT
                TABLE_NAME,
                COLUMN_NAME,
                CONSTRAINT_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
               AND CONSTRAINT_NAME LIKE 'fk_%'
             ORDER BY TABLE_NAME, CONSTRAINT_NAME",
            [$this->databaseName()]
        );
    }

    public function getAllTableNames(): array
    {
        return $this->fetchColumn(
            "SELECT TABLE_NAME
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ?
             ORDER BY TABLE_NAME",
            [$this->databaseName()]
        );
    }

    public function getAllTableNamesArray(): array
    {
        $stmt = $this->query(
            "SELECT TABLE_NAME
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ?
             ORDER BY TABLE_NAME",
            [$this->databaseName()]
        );

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function validateSharedConnection(): array
    {
        $checks = [];

        $checks['database_exists'] = $this->tableExists('users');

        $checks['tables_count'] = count($this->getAllTableNamesArray());

        $users = $this->count('users');
        $checks['has_users'] = $users > 0;
        $checks['total_users'] = $users;

        $row = $this->fetchOne(
            "SELECT COUNT(*) as total_tables
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ?",
            [$this->databaseName()]
        );
        $checks['total_tables_in_db'] = (int) ($row['total_tables'] ?? 0);

        $now = $this->fetchColumn("SELECT NOW()");
        $checks['server_time'] = $now;

        return $checks;
    }
}
