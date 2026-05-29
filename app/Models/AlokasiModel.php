<?php

namespace App\Models;

class AlokasiModel extends BaseModel
{
    protected string $table = 'alokasi_petugas';

    public function getTableName(): string
    {
        return $this->table;
    }

    public function totalRecords(): int
    {
        return $this->count($this->table);
    }

    public function getColumns(): array
    {
        return $this->getTableColumns($this->table);
    }

    public function countByPosisi(string $posisi): int
    {
        return $this->count($this->table, 'posisi_tugas = ?', [$posisi]);
    }

    public function countByStatus(string $status): int
    {
        return $this->count($this->table, 'status_alokasi = ?', [$status]);
    }

    public function countActive(): int
    {
        return $this->count($this->table, "status_alokasi = 'aktif'");
    }
}
