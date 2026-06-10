<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Helpers\Env;
use OpenSpout\Reader\XLSX\Reader;

Env::load(__DIR__ . '/../.env');
$pdo = Database::instance()->pdo();
$file = __DIR__ . '/../data/Data_Mitra_SE2026.xlsx';

if (!is_file($file)) {
    die("File not found: $file\n");
}

echo "Starting integration of Mitra data with Assignment Position...\n";

$reader = new Reader();
$reader->open($file);

$berhasil = 0;
$update = 0;
$gagal = 0;
$skip = 0;

// Helper function to extract text from "(010) KENCONG" format
function extractValue($str) {
    if (preg_match('/\)\s*(.*)$/', (string)$str, $matches)) {
        return trim($matches[1]);
    }
    return trim((string)$str);
}

foreach ($reader->getSheetIterator() as $sheet) {
    // We assume Sheet1 or first sheet based on previous analysis
    $targetSheet = 'Sheet1'; 
    if ($sheet->getName() !== $targetSheet) continue;

    foreach ($sheet->getRowIterator() as $idx => $row) {
        if ($idx <= 1) continue; // Skip header

        $cells = $row->toArray();
        if (count($cells) < 20) continue;

        // Pemetaan Kolom
        $nama_lengkap   = trim((string)($cells[0] ?? ''));
        $posisi_daftar  = trim((string)($cells[1] ?? ''));
        
        // Kolom baru "Posisi yang ditetapkan/ditugaskan" diasumsikan ada di kolom ke-22 (index 21)
        // Jika belum ada di file fisik, kita bisa menginisialisasinya dengan nilai posisi_daftar sebagai default awal
        $posisi_tugas   = trim((string)($cells[21] ?? $posisi_daftar)); 
        
        $alamat_lengkap = trim((string)($cells[4] ?? ''));
        $nmkec_domisili = extractValue($cells[7] ?? '');
        $nmdesa_domisili = extractValue($cells[8] ?? '');
        $jenis_kelamin  = trim((string)($cells[11] ?? '')) === 'Lk' ? 'Lk' : 'Pr';
        $pendidikan     = trim((string)($cells[12] ?? ''));
        $pekerjaan      = trim((string)($cells[13] ?? ''));
        $no_hp          = str_replace([' ', '-', '+62'], ['', '', '0'], trim((string)($cells[18] ?? '')));
        $nik            = trim((string)($cells[19] ?? ''));
        $email          = trim((string)($cells[20] ?? ''));

        if (empty($nik) || empty($nama_lengkap)) {
            $skip++;
            continue;
        }

        // Generate Username (nik) & Default Password (nik)
        $username = $nik;
        
        // Tentukan role aplikasi berdasarkan posisi_tugas (yang ditetapkan)
        $role = 'mitra';
        if (stripos($posisi_tugas, 'PML') !== false) $role = 'pml';
        elseif (stripos($posisi_tugas, 'PCL') !== false) $role = 'pcl';
        elseif (stripos($posisi_daftar, 'PML') !== false) $role = 'pml';
        elseif (stripos($posisi_daftar, 'PCL') !== false) $role = 'pcl';

        try {
            // Cek duplikasi via NIK atau Username
            $stmt = $pdo->prepare("SELECT id FROM users WHERE nik = ? OR username = ?");
            $stmt->execute([$nik, $username]);
            $user = $stmt->fetch();

            if ($user) {
                // Update data eksisting
                $sql = "UPDATE users SET 
                            nama_lengkap = ?, jenis_kelamin = ?, no_hp = ?, 
                            pendidikan = ?, pekerjaan = ?, alamat_lengkap = ?, 
                            posisi_daftar = ?, posisi_tugas = ?, role = ?, 
                            kecamatan_domisili = ?, desa_domisili = ?
                        WHERE id = ?";
                $pdo->prepare($sql)->execute([
                    $nama_lengkap, $jenis_kelamin, $no_hp, 
                    $pendidikan, $pekerjaan, $alamat_lengkap, 
                    $posisi_daftar, $posisi_tugas, $role, 
                    $nmkec_domisili, $nmdesa_domisili,
                    $user['id']
                ]);
                $update++;
            } else {
                // Insert baru
                $password = password_hash($nik, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (
                            username, email, nik, password, nama_lengkap, 
                            jenis_kelamin, no_hp, pendidikan, pekerjaan, 
                            alamat_lengkap, role, posisi_daftar, posisi_tugas, status_akun,
                            kecamatan_domisili, desa_domisili
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)";
                $pdo->prepare($sql)->execute([
                    $username, $email, $nik, $password, $nama_lengkap,
                    $jenis_kelamin, $no_hp, $pendidikan, $pekerjaan,
                    $alamat_lengkap, $role, $posisi_daftar, $posisi_tugas,
                    $nmkec_domisili, $nmdesa_domisili
                ]);
                $berhasil++;
            }
        } catch (\Exception $e) {
            echo "Error at row $idx ($nama_lengkap): " . $e->getMessage() . "\n";
            $gagal++;
        }
    }
}

$reader->close();

echo "\nIntegration with Positions Finished:\n";
echo "- New Mitra: $berhasil\n";
echo "- Updated: $update\n";
echo "- Failed: $gagal\n";
echo "- Skipped (invalid): $skip\n";
