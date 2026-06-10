<?php
/**
 * scripts/seed_missing_ppl.php
 *
 * Menambahkan 4 PPL yang emailnya tidak ditemukan di tabel users.
 * Password default: Ppl@2026 (harus diganti saat login pertama).
 * Dry-run default.
 *
 * Usage:
 *   php scripts/seed_missing_ppl.php              # dry-run
 *   php scripts/seed_missing_ppl.php --execute     # apply
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;

$execute = in_array('--execute', $argv);
$dryRun = !$execute;

$db = Database::instance();
$pdo = $db->pdo();

$missing = [
    ['username' => 'iturofiq',     'email' => 'iturofiq@gmail.com',         'nama_lengkap' => 'Ita Rofiqoh',           'role' => 'pcl'],
    ['username' => 'anyaninna',    'email' => 'anyaninna074@gmail.com',      'nama_lengkap' => 'Anya Ninn',             'role' => 'pcl'],
    ['username' => 'nurlailatul',  'email' => 'nurlailatuljh.87@gmail.com',  'nama_lengkap' => 'Nur Lailatul Jannah',  'role' => 'pcl'],
    ['username' => 'bundaiif',     'email' => 'bundaiif28@mail.com',         'nama_lengkap' => 'Bunda Iif',             'role' => 'pcl'],
];

echo "=== Seed Missing PPL Users ===\n\n";

$passwordHash = password_hash('Ppl@2026', PASSWORD_BCRYPT, ['cost' => 12]);
$added = 0;
$alreadyExists = 0;

foreach ($missing as $user) {
    // Cek apakah email sudah ada
    $existing = $db->fetchColumn("SELECT id FROM users WHERE email = ? LIMIT 1", [$user['email']]);
    if ($existing) {
        echo "  [SKIP] {$user['email']} sudah ada (user_id={$existing})\n";
        $alreadyExists++;
        continue;
    }

    // Cek apakah username sudah ada
    $existingUser = $db->fetchColumn("SELECT id FROM users WHERE username = ? LIMIT 1", [$user['username']]);
    if ($existingUser) {
        $user['username'] = $user['username'] . '_lk';
        echo "  [RENAME] Username sudah ada, pakai {$user['username']}\n";
    }

    if ($dryRun) {
        echo "  [DRY-RUN] Akan menambah: {$user['email']} ({$user['nama_lengkap']})\n";
        $added++;
        continue;
    }

    try {
        $pdo->prepare("
            INSERT INTO users (username, password, email, nama_lengkap, role, status_akun, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW())
        ")->execute([
            $user['username'],
            $passwordHash,
            $user['email'],
            $user['nama_lengkap'],
            $user['role'],
        ]);
        echo "  [OK] Ditambah: {$user['email']} (user_id={$pdo->lastInsertId()})\n";
        $added++;
    } catch (Throwable $e) {
        echo "  [ERR] {$user['email']}: " . $e->getMessage() . "\n";
    }
}

echo "\n--- Ringkasan ---\n";
echo "  Ditambah: {$added}\n";
echo "  Sudah ada: {$alreadyExists}\n";

if (!$dryRun) {
    echo "\nPassword default: Ppl@2026\n";
    echo "User harus mengganti password saat login pertama.\n";
}
