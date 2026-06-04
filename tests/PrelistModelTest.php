<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use App\Models\PrelistModel;

final class PrelistModelTest extends TestCase
{
    private static PrelistModel $model;

    public static function setUpBeforeClass(): void
    {
        self::$model = new PrelistModel();
    }

    #[Test]
    public function isImported_returns_bool(): void
    {
        $this->assertIsBool(self::$model->isImported());
    }

    #[Test]
    public function getImportStatus_returns_counts(): void
    {
        $status = self::$model->getImportStatus();
        $this->assertArrayHasKey('prelist_kabkota', $status);
        $this->assertArrayHasKey('prelist_kecamatan', $status);
        $this->assertArrayHasKey('prelist_sls', $status);
        $this->assertGreaterThan(0, $status['prelist_kabkota']);
    }

    #[Test]
    public function getKpiJatim_returns_kpi(): void
    {
        $kpi = self::$model->getKpiJatim();
        $this->assertArrayHasKey('total_sls', $kpi);
        $this->assertArrayHasKey('total_kk', $kpi);
        $this->assertArrayHasKey('total_usaha', $kpi);
        $this->assertGreaterThan(0, $kpi['total_sls']);
    }

    #[Test]
    public function getKomposisiUsahaPerKab_returns_data(): void
    {
        $data = self::$model->getKomposisiUsahaPerKab();
        $this->assertIsArray($data);
        if (!empty($data)) {
            $this->assertArrayHasKey('total', $data[0]);
            $this->assertArrayHasKey('umk', $data[0]);
        }
    }

    #[Test]
    public function getPerbandinganSe2016_returns_comparison(): void
    {
        $data = self::$model->getPerbandinganSe2016();
        $this->assertIsArray($data);
        if (!empty($data)) {
            $this->assertArrayHasKey('se2016', $data[0]);
            $this->assertArrayHasKey('pct_growth', $data[0]);
        }
    }

    #[Test]
    public function getBebanKerjaKecamatan_returns_workload(): void
    {
        $data = self::$model->getBebanKerjaKecamatan('3509');
        $this->assertIsArray($data);
        if (!empty($data)) {
            $this->assertArrayHasKey('muatan_rs', $data[0]);
            $this->assertArrayHasKey('total_beban', $data[0]);
        }
    }

    #[Test]
    public function getAnomaliKecamatan_returns_anomalies(): void
    {
        $data = self::$model->getAnomaliKecamatan('3509');
        $this->assertIsArray($data);
    }

    #[Test]
    public function getAnomaliSls_returns_anomalous_sls(): void
    {
        $data = self::$model->getAnomaliSls('3509');
        $this->assertIsArray($data);
    }

    #[Test]
    public function getAnomaliSummary_has_keys(): void
    {
        $s = self::$model->getAnomaliSummary('3509');
        $this->assertArrayHasKey('sls_kk_0', $s);
        $this->assertArrayHasKey('sls_muatan_tinggi', $s);
    }

    #[Test]
    public function getMapKecamatan_has_structure(): void
    {
        $data = self::$model->getMapKecamatan('3509');
        $this->assertIsArray($data);
        if (!empty($data)) {
            $row = $data[0];
            $this->assertArrayHasKey('kd_kec', $row);
            $this->assertArrayHasKey('muatan_rs', $row);
        }
    }

    #[Test]
    public function getWorkloadStats_has_aggregates(): void
    {
        $stats = self::$model->getWorkloadStats('3509');
        $this->assertArrayHasKey('total_kecamatan', $stats);
        $this->assertArrayHasKey('total_muatan', $stats);
        $this->assertGreaterThan(0, $stats['total_kecamatan']);
    }

    #[Test]
    public function getKecamatanByKab_returns_kec(): void
    {
        $data = self::$model->getKecamatanByKab('3509');
        $this->assertIsArray($data);
        if (!empty($data)) {
            $this->assertArrayHasKey('nm_kec', $data[0]);
        }
    }
}
