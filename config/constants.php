<?php

/**
 * Role Constants — harus sinkron dengan ENUM users.role di database
 *
 * Status per Juni 2026:
 *   - admin      : 4 active (admin, pegawai01, pegawai02, dll)
 *   - operator   : 0 users (role di-deklarasikan tapi belum ada user)
 *                  → permission check masih include operator untuk backward compat
 *                  → jika di-remove: hapus dari semua ROLE_* arrays + 23 referensi
 *                  → rekomendasi R3.4: biarkan sampai ada kebutuhan eksplisit
 *   - pegawai    : 1 user (pegawai3509, placeholder) + 5 baru (Jun 2026) via R3.1
 *   - mitra      : 2,158 active (99,95% tanpa kecamatan_bertugas, R1.1 backfill)
 *   - pml/pcl/task_force : 1 each (test accounts, R3.3 onboarding)
 *   - panitia    : 1 test account
 *   - pj         : 0 users (legacy role)
 */

defined('ROLE_ADMIN')      || define('ROLE_ADMIN',      'admin');
defined('ROLE_OPERATOR')   || define('ROLE_OPERATOR',   'operator');
defined('ROLE_PEGAWAI')    || define('ROLE_PEGAWAI',    'pegawai');
defined('ROLE_MITRA')      || define('ROLE_MITRA',      'mitra');
defined('ROLE_PML')        || define('ROLE_PML',        'pml');
defined('ROLE_PCL')        || define('ROLE_PCL',        'pcl');
defined('ROLE_TASK_FORCE') || define('ROLE_TASK_FORCE', 'task_force');

/**
 * Label role (Indonesia)
 */
const ROLE_LABELS = [
    ROLE_ADMIN      => 'Administrator',
    ROLE_OPERATOR   => 'Operator',
    ROLE_PEGAWAI    => 'Pegawai BPS',
    ROLE_MITRA      => 'Mitra',
    ROLE_PML        => 'PML',
    ROLE_PCL        => 'PCL',
    ROLE_TASK_FORCE => 'Task Force',
];

/**
 * Role yang berhak mengakses dashboard
 */
const DASHBOARD_ROLES = [
    ROLE_ADMIN,
    ROLE_OPERATOR,
    ROLE_PEGAWAI,
    ROLE_TASK_FORCE,
    ROLE_PML,
    ROLE_PCL,
];

/**
 * Role yang bisa mengelola assignment
 */
const ASSIGNMENT_ROLES = [
    ROLE_ADMIN,
    ROLE_OPERATOR,
];

/**
 * Halaman default setelah login berdasarkan role
 */
const ROLE_HOME = [
    ROLE_ADMIN      => '?page=dashboard',
    ROLE_OPERATOR   => '?page=dashboard',
    ROLE_PEGAWAI    => '?page=dashboard',
    ROLE_TASK_FORCE => '?page=dashboard&sub=monitoring',
    ROLE_PML        => '?page=dashboard&sub=monitoring',
    ROLE_PCL        => '?page=dashboard&sub=monitoring',
];

/**
 * Role-based page access map
 * Array: page/sub => [allowed_roles]  (null = semua role DASHBOARD_ROLES)
 */
const PAGE_ACCESS = [
    'dashboard' => [
        ''           => DASHBOARD_ROLES,
        'import'     => [ROLE_ADMIN, ROLE_OPERATOR],
        'assignment' => [ROLE_ADMIN, ROLE_OPERATOR, ROLE_PEGAWAI],
        'monitoring' => DASHBOARD_ROLES,
        'workload'   => [ROLE_ADMIN, ROLE_OPERATOR, ROLE_PEGAWAI],
        'wilayah'    => [ROLE_ADMIN, ROLE_OPERATOR, ROLE_TASK_FORCE],
        'petugas'    => [ROLE_ADMIN],
        'petugas-lapangan'  => [ROLE_ADMIN],
        'audit'      => [ROLE_ADMIN, ROLE_OPERATOR],
        'report'     => [ROLE_ADMIN, ROLE_OPERATOR],
        'insight'    => [ROLE_ADMIN, ROLE_OPERATOR, ROLE_TASK_FORCE, ROLE_PML, ROLE_PCL],
        'pegawai-activity' => [ROLE_ADMIN, ROLE_OPERATOR],
    ],
];
