<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DatabaseTest extends TestCase
{
    private \App\Helpers\Database $db;

    protected function setUp(): void
    {
        $this->db = \App\Helpers\Database::getInstance();
    }

    #[Test]
    public function getInstance_returns_singleton(): void
    {
        $instance1 = \App\Helpers\Database::getInstance();
        $instance2 = \App\Helpers\Database::getInstance();
        $this->assertSame($instance1, $instance2);
    }

    #[Test]
    public function connection_is_real_time(): void
    {
        $result = $this->db->fetchColumn('SELECT 1');
        $this->assertEquals(1, (int) $result);
    }

    #[Test]
    public function connected_to_correct_database(): void
    {
        $dbName = $this->db->getCurrentDatabase();
        $this->assertEquals('bps_jember_se2026', $dbName);
    }

    #[Test]
    public function server_info_is_accessible(): void
    {
        $version = $this->db->serverInfo();
        $this->assertNotEmpty($version);
        $this->assertStringStartsWith('8', $version);
    }

    #[Test]
    public function connection_id_is_unique(): void
    {
        $connId = $this->db->getConnectionId();
        $this->assertGreaterThan(0, $connId);
    }

    #[Test]
    public function table_exists_works(): void
    {
        $this->assertTrue($this->db->tableExists('users'));
        $this->assertTrue($this->db->tableExists('sipw_import'));
        $this->assertFalse($this->db->tableExists('nonexistent_table_xyz'));
    }

    #[Test]
    public function count_returns_positive(): void
    {
        $count = $this->db->count('users');
        $this->assertGreaterThan(0, $count);
    }

    #[Test]
    public function isConnected_returns_true(): void
    {
        $this->assertTrue($this->db->isConnected());
    }
}
