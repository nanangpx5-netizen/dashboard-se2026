<?php

declare(strict_types=1);

use App\Helpers\Cache;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CacheTest extends TestCase
{
    private string $originalCachePath;

    protected function setUp(): void
    {
        $tmp = sys_get_temp_dir() . '/cache_test_' . bin2hex(random_bytes(4));
        if (!defined('CACHE_PATH')) {
            define('CACHE_PATH', $tmp);
        }
        mkdir($tmp, 0755, true);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        $dir = defined('CACHE_PATH') ? CACHE_PATH : '';
        if ($dir && is_dir($dir)) {
            array_map('unlink', glob($dir . '/*.cache') ?: []);
            rmdir($dir);
        }
    }

    #[Test]
    public function set_and_get_returns_value(): void
    {
        Cache::set('test_key', 'hello', 60);
        $this->assertSame('hello', Cache::get('test_key'));
    }

    #[Test]
    public function get_missing_returns_default(): void
    {
        $this->assertNull(Cache::get('nonexistent'));
        $this->assertSame('fallback', Cache::get('nonexistent', 'fallback'));
    }

    #[Test]
    public function get_expired_returns_default(): void
    {
        Cache::set('quick', 'expired', 0);
        sleep(1);
        $this->assertNull(Cache::get('quick'));
    }

    #[Test]
    public function forget_removes_value(): void
    {
        Cache::set('temp', 'data', 60);
        $this->assertSame('data', Cache::get('temp'));
        Cache::forget('temp');
        $this->assertNull(Cache::get('temp'));
    }

    #[Test]
    public function remember_caches_callback_result(): void
    {
        $counter = 0;
        $result = Cache::remember('cb_test', 60, function () use (&$counter) {
            $counter++;
            return 'computed_' . $counter;
        });
        $this->assertSame('computed_1', $result);
        $this->assertSame(1, $counter);

        $result2 = Cache::remember('cb_test', 60, function () use (&$counter) {
            $counter++;
            return 'computed_' . $counter;
        });
        $this->assertSame('computed_1', $result2);
        $this->assertSame(1, $counter);
    }

    #[Test]
    public function flush_clears_all(): void
    {
        Cache::set('a', 1, 60);
        Cache::set('b', 2, 60);
        Cache::flush();
        $this->assertNull(Cache::get('a'));
        $this->assertNull(Cache::get('b'));
    }

    #[Test]
    public function gc_removes_expired(): void
    {
        Cache::set('fresh', 'ok', 60);
        Cache::set('stale', 'gone', 0);
        sleep(1);

        $stats = Cache::gc();
        $this->assertGreaterThanOrEqual(1, $stats['deleted']);
        $this->assertSame('ok', Cache::get('fresh'));
    }

    #[Test]
    public function concurrent_reads_do_not_corrupt(): void
    {
        Cache::set('concurrent', 'safe_value', 60);
        $readers = [];
        for ($i = 0; $i < 10; $i++) {
            $readers[] = Cache::get('concurrent');
        }
        foreach ($readers as $val) {
            $this->assertSame('safe_value', $val);
        }
    }
}
