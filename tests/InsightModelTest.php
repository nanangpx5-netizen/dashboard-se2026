<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use App\Models\InsightModel;

final class InsightModelTest extends TestCase
{
    private static InsightModel $model;

    public static function setUpBeforeClass(): void
    {
        self::$model = new InsightModel();
    }

    #[Test]
    public function getExecutiveSummary_has_all_keys(): void
    {
        $s = self::$model->getExecutiveSummary();
        $this->assertArrayHasKey('total_sls', $s);
        $this->assertArrayHasKey('total_kec', $s);
        $this->assertArrayHasKey('total_kk', $s);
        $this->assertArrayHasKey('total_muatan', $s);
        $this->assertArrayHasKey('total_btt', $s);
        $this->assertArrayHasKey('total_bku', $s);
        $this->assertArrayHasKey('total_usaha', $s);
        $this->assertArrayHasKey('prelist_sls', $s);
        $this->assertArrayHasKey('prelist_kec', $s);
        $this->assertArrayHasKey('total_assignment', $s);
        $this->assertArrayHasKey('coverage_pct', $s);
        $this->assertArrayHasKey('delta_sls_vs_prelist', $s);
        $this->assertArrayHasKey('avg_muatan', $s);
        $this->assertArrayHasKey('avg_kk', $s);
        $this->assertGreaterThan(0, $s['total_sls']);
        $this->assertGreaterThan(0, $s['total_kec']);
    }

    #[Test]
    public function getAnomaliPerKecamatan_returns_data(): void
    {
        $data = self::$model->getAnomaliPerKecamatan('09');
        $this->assertIsArray($data);
        if (!empty($data)) {
            $this->assertArrayHasKey('nmkec', $data[0]);
            $this->assertArrayHasKey('sls', $data[0]);
            $this->assertArrayHasKey('mu_zero', $data[0]);
            $this->assertArrayHasKey('anomali_pct', $data[0]);
        }
    }

    #[Test]
    public function getBebanKerjaPerKecamatan_has_kategori(): void
    {
        $data = self::$model->getBebanKerjaPerKecamatan('09');
        $this->assertIsArray($data);
        if (!empty($data)) {
            $this->assertArrayHasKey('kategori_beban', $data[0]);
            $this->assertContains($data[0]['kategori_beban'], ['RINGAN', 'SEDANG', 'BERAT']);
        }
    }

    #[Test]
    public function getDistribusiMuatan_has_bins(): void
    {
        $data = self::$model->getDistribusiMuatan('09');
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));
        $totalPct = array_sum(array_column($data, 'pct'));
        $this->assertEqualsWithDelta(100, $totalPct, 1);
    }

    #[Test]
    public function getCoverageGap_has_diff(): void
    {
        $data = self::$model->getCoverageGap('3509');
        $this->assertIsArray($data);
        if (!empty($data)) {
            $this->assertArrayHasKey('sipw_sls', $data[0]);
            $this->assertArrayHasKey('diff_muatan', $data[0]);
        }
    }

    #[Test]
    public function getRekomendasi_returns_recommendations(): void
    {
        $summary = self::$model->getExecutiveSummary();
        $anomali = self::$model->getAnomaliPerKecamatan('09');
        $beban   = self::$model->getBebanKerjaPerKecamatan('09');
        $recs = self::$model->getRekomendasi($summary, $anomali, $beban);
        $this->assertIsArray($recs);
        if (!empty($recs)) {
            $this->assertArrayHasKey('level', $recs[0]);
            $this->assertArrayHasKey('title', $recs[0]);
            $this->assertArrayHasKey('desc', $recs[0]);
            $this->assertContains($recs[0]['level'], ['success', 'warning', 'danger', 'info']);
        }
    }

    #[Test]
    public function getTopSlsAnomali_returns_sls(): void
    {
        $data = self::$model->getTopSlsAnomali('09', 10, 'muatan_zero');
        $this->assertIsArray($data);
        $this->assertLessThanOrEqual(10, count($data));
    }

    #[Test]
    public function getUserPool_returns_rows(): void
    {
        $pool = self::$model->getUserPool();
        $this->assertIsArray($pool);
        if (!empty($pool)) {
            $this->assertArrayHasKey('role', $pool[0]);
            $this->assertArrayHasKey('status_akun', $pool[0]);
            $this->assertArrayHasKey('jumlah', $pool[0]);
        }
    }

    #[Test]
    public function getDataQuality_has_collation_info(): void
    {
        $q = self::$model->getDataQuality();
        $this->assertArrayHasKey('collation_consistent', $q);
        $this->assertArrayHasKey('user_count_active', $q);
        $this->assertArrayHasKey('assignment_count', $q);
        $this->assertIsBool($q['collation_consistent']);
    }
}
