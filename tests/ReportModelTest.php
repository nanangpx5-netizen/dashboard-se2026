<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use App\Models\ReportModel;

final class ReportModelTest extends TestCase
{
    private static ReportModel $model;

    public static function setUpBeforeClass(): void
    {
        self::$model = new ReportModel();
    }

    #[Test]
    public function dashboardSnapshot_has_all_keys(): void
    {
        $s = self::$model->dashboardSnapshot();
        $this->assertArrayHasKey('total_kecamatan', $s);
        $this->assertArrayHasKey('total_desa', $s);
        $this->assertArrayHasKey('total_sls', $s);
        $this->assertArrayHasKey('total_muatan', $s);
        $this->assertArrayHasKey('total_kk', $s);
        $this->assertArrayHasKey('total_usaha', $s);
        $this->assertArrayHasKey('assigned', $s);
        $this->assertArrayHasKey('proses', $s);
        $this->assertArrayHasKey('selesai', $s);
        $this->assertArrayHasKey('total_pcl', $s);
        $this->assertArrayHasKey('total_pml', $s);
        $this->assertGreaterThan(0, $s['total_kecamatan']);
        $this->assertGreaterThan(0, $s['total_sls']);
    }

    #[Test]
    public function executiveSummary_has_all_keys(): void
    {
        $s = self::$model->executiveSummary();
        $this->assertArrayHasKey('total_kec', $s);
        $this->assertArrayHasKey('total_desa', $s);
        $this->assertArrayHasKey('total_sls', $s);
        $this->assertArrayHasKey('total_kk', $s);
        $this->assertArrayHasKey('total_usaha', $s);
        $this->assertArrayHasKey('total_muatan', $s);
        $this->assertArrayHasKey('assigned', $s);
        $this->assertArrayHasKey('dikerjakan', $s);
        $this->assertArrayHasKey('selesai', $s);
        $this->assertArrayHasKey('kebutuhan_pcl', $s);
        $this->assertArrayHasKey('kebutuhan_pml', $s);
    }

    #[Test]
    public function rekapKecamatan_has_all_columns(): void
    {
        $data = self::$model->rekapKecamatan();
        $this->assertIsArray($data);
        if (!empty($data)) {
            $row = $data[0];
            $this->assertArrayHasKey('total_sls', $row);
            $this->assertArrayHasKey('assigned', $row);
            $this->assertArrayHasKey('proses', $row);
            $this->assertArrayHasKey('selesai', $row);
            $this->assertArrayHasKey('total_kk', $row);
            $this->assertArrayHasKey('jumlah_pcl', $row);
        }
    }

    #[Test]
    public function rekapPencacah_returns_array(): void
    {
        $data = self::$model->rekapPencacah();
        $this->assertIsArray($data);
    }

    #[Test]
    public function rekapPengawas_returns_array(): void
    {
        $data = self::$model->rekapPengawas();
        $this->assertIsArray($data);
    }

    #[Test]
    public function rekapTaskForce_returns_array(): void
    {
        $data = self::$model->rekapTaskForce();
        $this->assertIsArray($data);
    }

    #[Test]
    public function kecamatanList_returns_distinct(): void
    {
        $list = self::$model->kecamatanList();
        $this->assertIsArray($list);
        $this->assertGreaterThan(0, count($list));
        $kodeKec = array_unique(array_map(fn($r) => $r['kdkec'] ?? '', $list));
        $this->assertEquals(count($list), count($kodeKec), 'Kecamatan list must be distinct');
    }

    #[Test]
    public function prelistSummary_has_keys(): void
    {
        $s = self::$model->prelistSummary();
        $this->assertArrayHasKey('total_sls', $s);
        $this->assertArrayHasKey('total_kk', $s);
        $this->assertArrayHasKey('total_utp', $s);
        $this->assertArrayHasKey('total_muatan', $s);
    }

    #[Test]
    public function prelistPerKec_returns_array(): void
    {
        $data = self::$model->prelistPerKec();
        $this->assertIsArray($data);
        if (!empty($data)) {
            $this->assertArrayHasKey('total_sls', $data[0]);
            $this->assertArrayHasKey('total_muatan', $data[0]);
        }
    }
}
