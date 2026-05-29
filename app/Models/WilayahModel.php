<?php

namespace App\Models;

class WilayahModel extends BaseModel
{
    protected string $table = 'wilayah_kerja';

    public function getTableName(): string
    {
        return $this->table;
    }

    public function totalKecamatan(): int
    {
        return $this->count($this->table);
    }

    public function getAll(): array
    {
        return $this->fetchAll(
            "SELECT id, kode_kecamatan, nama_kecamatan, kode_wilkerstat,
                    nama_wilkerstat, kebutuhan_pcl, kebutuhan_pml,
                    terisi_pcl, terisi_pml
             FROM {$this->table}
             ORDER BY nama_kecamatan ASC"
        );
    }

    public function getColumns(): array
    {
        return $this->getTableColumns($this->table);
    }

    public function totalKebutuhanPcl(): int
    {
        return (int) $this->fetchColumn(
            "SELECT COALESCE(SUM(kebutuhan_pcl), 0) FROM {$this->table}"
        );
    }

    public function totalKebutuhanPml(): int
    {
        return (int) $this->fetchColumn(
            "SELECT COALESCE(SUM(kebutuhan_pml), 0) FROM {$this->table}"
        );
    }

    public function totalTerisiPcl(): int
    {
        return (int) $this->fetchColumn(
            "SELECT COALESCE(SUM(terisi_pcl), 0) FROM {$this->table}"
        );
    }

    public function totalTerisiPml(): int
    {
        return (int) $this->fetchColumn(
            "SELECT COALESCE(SUM(terisi_pml), 0) FROM {$this->table}"
        );
    }
}
