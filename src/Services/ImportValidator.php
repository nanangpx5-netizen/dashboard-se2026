<?php

namespace App\Services;

/**
 * ImportValidator — Validasi header dan baris data Excel SIPW
 *
 * Fitur:
 * - Validasi header wajib vs opsional dengan fuzzy matching
 * - Validasi tipe data per kolom
 * - Error collection dengan nomor baris
 * - Memory efficient (tidak menyimpan semua error di array)
 */
class ImportValidator
{
    /** Kolom wajib — harus ada di file Excel */
    public const REQUIRED_COLUMNS = [
        'kdkec', 'kddesa',
    ];

    /** Kolom yang sangat direkomendasikan */
    public const RECOMMENDED_COLUMNS = [
        'nmsls', 'muatan',
    ];

    /** Mapping nama kolom SIPW → field database */
    public const COLUMN_MAP = [
        'idfrs'         => ['idfrs', 'id_frs', 'id_frs_sipw', 'no_urut', 'no', 'no_urut_sls'],
        'semester'      => ['semester', 'periode', 'period', 'periode_data', 'semester_data'],
        'idsubsls'      => ['idsubsls', 'id_sub_sls', 'sub_sls', 'kode_sls_detail', 'sub_sls_id'],
        'kdprov'        => ['kdprov', 'kode_prov', 'kode_provinsi', 'prov', 'provinsi'],
        'kdkab'         => ['kdkab', 'kode_kab', 'kode_kabupaten', 'kab', 'kabupaten'],
        'kdkec'         => ['kdkec', 'kode_kec', 'kode_kecamatan', 'kec', 'kecamatan'],
        'kddesa'        => ['kddesa', 'kode_desa', 'kode_desa_kelurahan', 'desa', 'desa_kelurahan', 'kelurahan'],
        'kdsls'         => ['kdsls', 'kode_sls', 'kode_blok_sensus', 'sls', 'blok_sensus', 'nks'],
        'nmprov'        => ['nmprov', 'nama_prov', 'nama_provinsi', 'provinsi'],
        'nmkab'         => ['nmkab', 'nama_kab', 'nama_kabupaten', 'kabupaten'],
        'nmkec'         => ['nmkec', 'nama_kec', 'nama_kecamatan', 'kecamatan'],
        'nmdesa'        => ['nmdesa', 'nama_desa', 'nama_desa_kelurahan', 'desa_kelurahan'],
        'nmsls'         => ['nmsls', 'nama_sls', 'nama_blok_sensus', 'nama_sls_rw', 'nama_sls_dusun'],
        'nama_ketua'    => ['nama_ketua', 'ketua', 'nama_ketua_sls', 'nama_kepala_sls', 'ketua_sls', 'kepala_sls'],
        'kk'            => ['kk', 'jml_kk', 'jumlah_kk', 'kepala_keluarga', 'jml_kepala_keluarga'],
        'btt'           => ['btt', 'jml_btt', 'jumlah_btt', 'bangunan_tt', 'bangunan_tempat_tinggal'],
        'bttk'          => ['bttk', 'jml_bttk', 'jumlah_bttk', 'btt_khusus', 'bangunan_tt_khusus'],
        'bku'           => ['bku', 'jml_bku', 'jumlah_bku', 'bangunan_usaha', 'bangunan_kedudukan_usaha'],
        'bbtt_nonusaha' => ['bbtt_nonusaha', 'btt_non_usaha', 'non_usaha', 'btt_nonusaha', 'jml_non_usaha'],
        'usaha'         => ['usaha', 'jml_usaha', 'jumlah_usaha', 'unit_usaha', 'usaha_non_pertanian'],
        'muatan'        => ['muatan', 'total_muatan', 'beban', 'bobot', 'total', 'jumlah_muatan'],
    ];

    /** Tipe data per kolom untuk validasi */
    public const COLUMN_TYPES = [
        'idfrs'         => 'integer',
        'semester'      => 'string',
        'idsubsls'      => 'string',
        'kdprov'        => 'string',
        'kdkab'         => 'string',
        'kdkec'         => 'string',
        'kddesa'        => 'string',
        'kdsls'         => 'string',
        'nmprov'        => 'string',
        'nmkab'         => 'string',
        'nmkec'         => 'string',
        'nmdesa'        => 'string',
        'nmsls'         => 'string',
        'nama_ketua'    => 'string',
        'kk'            => 'integer',
        'btt'           => 'integer',
        'bttk'          => 'integer',
        'bku'           => 'integer',
        'bbtt_nonusaha' => 'integer',
        'usaha'         => 'integer',
        'muatan'        => 'integer',
    ];

    private array $errors = [];
    private array $warnings = [];

    /**
     * Validasi header Excel dan return mapping kolom
     *
     * @param array $rawHeaders  Array of string header dari file
     * @return array  ['mapping' => [field => index], 'missing_required' => [], 'missing_recommended' => [], 'unmapped' => []]
     */
    public function validateHeaders(array $rawHeaders): array
    {
        $normalizedHeaders = $this->normalizeHeaders($rawHeaders);
        $mapping = [];
        $unmapped = [];

        // Cari mapping untuk setiap field database
        foreach (self::COLUMN_MAP as $field => $aliases) {
            $found = false;
            foreach ($normalizedHeaders as $index => $normalized) {
                if (in_array($normalized, $aliases, true)) {
                    $mapping[$field] = $index;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $mapping[$field] = null;
            }
        }

        // Catat header yang tidak ter-mapping
        foreach ($normalizedHeaders as $index => $normalized) {
            $isMapped = false;
            foreach (self::COLUMN_MAP as $field => $aliases) {
                if (in_array($normalized, $aliases, true)) {
                    $isMapped = true;
                    break;
                }
            }
            if (!$isMapped) {
                $unmapped[] = ['index' => $index, 'header' => $rawHeaders[$index]];
            }
        }

        // Cek kolom wajib
        $missingRequired = [];
        foreach (self::REQUIRED_COLUMNS as $field) {
            if ($mapping[$field] === null) {
                $missingRequired[] = $field;
            }
        }

        // Cek kolom rekomendasi
        $missingRecommended = [];
        foreach (self::RECOMMENDED_COLUMNS as $field) {
            if ($mapping[$field] === null) {
                $missingRecommended[] = $field;
            }
        }

        return [
            'mapping'               => $mapping,
            'missing_required'      => $missingRequired,
            'missing_recommended'   => $missingRecommended,
            'unmapped'              => $unmapped,
            'raw_headers'           => $rawHeaders,
            'normalized_headers'    => $normalizedHeaders,
        ];
    }

    /**
     * Validasi satu baris data berdasarkan mapping kolom
     *
     * @param array $cells     Array nilai cell per kolom
     * @param array $mapping   Mapping field => index hasil validateHeaders
     * @param int   $rowNumber Nomor baris (untuk error tracking)
     * @return array  ['valid' => bool, 'row' => array, 'errors' => array]
     */
    public function validateRow(array $cells, array $mapping, int $rowNumber): array
    {
        $row = [];
        $errors = [];

        foreach (self::COLUMN_MAP as $field => $aliases) {
            $index = $mapping[$field] ?? null;

            if ($index !== null && isset($cells[$index])) {
                $rawValue = $this->cleanValue($cells[$index]);
                $type = self::COLUMN_TYPES[$field] ?? 'string';
                $converted = $this->castValue($rawValue, $type);

                if ($converted === null && $rawValue !== null && $rawValue !== '') {
                    $errors[] = "Baris {$rowNumber}: Kolom '{$field}' bukan {$type} yang valid (\"{$rawValue}\")";
                }

                $row[$field] = $converted;
            } else {
                $row[$field] = null;
            }
        }

        // Validasi khusus: kdkec & kddesa wajib isi
        if (empty($row['kdkec'])) {
            $errors[] = "Baris {$rowNumber}: 'Kode Kecamatan' wajib diisi";
        }
        if (empty($row['kddesa'])) {
            $errors[] = "Baris {$rowNumber}: 'Kode Desa' wajib diisi";
        }

        return [
            'valid'  => empty($errors),
            'row'    => $row,
            'errors' => $errors,
        ];
    }

    /**
     * Normalisasi header: lowercase, strip special chars
     */
    public function normalizeHeader(string $header): string
    {
        $header = trim($header);
        $header = mb_strtolower($header, 'UTF-8');
        $header = preg_replace('/[^a-z0-9_]/', '_', $header);
        $header = preg_replace('/_{2,}/', '_', $header);
        $header = trim($header, '_');
        return $header;
    }

    /**
     * Normalisasi array header
     */
    public function normalizeHeaders(array $headers): array
    {
        return array_map([$this, 'normalizeHeader'], $headers);
    }

    /**
     * Bersihkan nilai cell
     */
    public function cleanValue(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
            return $value === '' ? null : $value;
        }
        if (is_float($value)) {
            // Handle angka desimal yang sebenarnya integer (e.g., 3509.0)
            if ($value == (int) $value) {
                return (int) $value;
            }
        }
        return $value;
    }

    /**
     * Cast nilai ke tipe yang diharapkan
     */
    public function castValue(mixed $value, string $type): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($type) {
            'integer' => $this->castToInteger($value),
            'float'   => is_numeric($value) ? (float) $value : null,
            default   => (string) $value,
        };
    }

    private function castToInteger(mixed $value): ?int
    {
        if (is_int($value)) return $value;
        if (is_float($value)) return (int) $value;
        if (is_string($value)) {
            $clean = preg_replace('/[^0-9\-]/', '', $value);
            if ($clean !== '' && is_numeric($clean)) {
                return (int) $clean;
            }
        }
        return null;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    public function addWarning(string $warning): void
    {
        $this->warnings[] = $warning;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
