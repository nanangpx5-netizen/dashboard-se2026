<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use App\Models\UserModel;

final class UserModelTest extends TestCase
{
    private static UserModel $model;
    private static int $testUserId = 0;

    public static function setUpBeforeClass(): void
    {
        self::$model = new UserModel();
    }

    #[Test]
    public function getUsers_returns_array(): void
    {
        $users = self::$model->getUsers(['admin', 'operator']);
        $this->assertIsArray($users);
        foreach ($users as $u) {
            $this->assertArrayHasKey('username', $u);
            $this->assertArrayHasKey('role', $u);
        }
    }

    #[Test]
    public function getUsers_with_filter_returns_filtered(): void
    {
        $admins = self::$model->getUsers(['admin', 'operator'], 'admin');
        foreach ($admins as $u) {
            $this->assertEquals('admin', $u['role']);
        }
    }

    #[Test]
    public function getRoleCounts_returns_per_role(): void
    {
        $counts = self::$model->getRoleCounts(['admin', 'operator']);
        $this->assertIsArray($counts);
        $roles = array_column($counts, 'role');
        $this->assertContains('admin', $roles);
    }

    #[Test]
    public function findById_returns_user(): void
    {
        $admin = self::$model->findById(1);
        $this->assertNotNull($admin);
        $this->assertArrayHasKey('username', $admin);
    }

    #[Test]
    public function findById_returns_null_for_invalid(): void
    {
        $this->assertNull(self::$model->findById(999999));
    }

    #[Test]
    public function findByUsername_returns_user(): void
    {
        $user = self::$model->findByUsername('admin');
        $this->assertNotNull($user);
        $this->assertArrayHasKey('id', $user);
    }

    #[Test]
    public function existsByUsername_returns_true_for_existing(): void
    {
        $this->assertTrue(self::$model->existsByUsername('admin'));
    }

    #[Test]
    public function existsByUsername_returns_false_for_nonexisting(): void
    {
        $this->assertFalse(self::$model->existsByUsername('__nonexistent_user_xyz__'));
    }

    #[Test]
    public function create_inserts_and_returns_id(): void
    {
        $id = self::$model->create([
            'username' => 'test_phpunit_' . time(),
            'email' => 'test@phpunit.dev',
            'password' => 'test123456',
            'role' => 'operator',
            'status_akun' => 'active',
        ]);
        $this->assertGreaterThan(0, $id);
        self::$testUserId = $id;
    }

    #[Test]
    #[Depends('create_inserts_and_returns_id')]
    public function update_modifies_user(): void
    {
        $email = 'updated_' . time() . '@phpunit.dev';
        self::$model->update(self::$testUserId, ['email' => $email]);
        $user = self::$model->findById(self::$testUserId);
        $this->assertEquals($email, $user['email']);
    }

    #[Test]
    #[Depends('create_inserts_and_returns_id')]
    public function updatePassword_changes_password(): void
    {
        self::$model->updatePassword(self::$testUserId, 'newpass123');
        $user = self::$model->findById(self::$testUserId);
        $this->assertNotEquals('test123456', $user['password']);
        $this->assertStringStartsWith('$2y$', $user['password']);
    }

    #[Test]
    #[Depends('create_inserts_and_returns_id')]
    public function cleanup_test_user(): void
    {
        $pdo = (new ReflectionClass(self::$model))->getProperty('pdo');
        $pdo->setAccessible(true);
        $pdo->getValue(self::$model)->prepare('DELETE FROM users WHERE id = ?')
            ->execute([self::$testUserId]);
        $this->assertNull(self::$model->findById(self::$testUserId));
    }
}
