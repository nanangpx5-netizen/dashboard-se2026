<?php
/** @var array|null $current_user */
$role = $current_user['role'] ?? '';
$isAdmin = $role === 'admin' || $role === 'operator';
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar d-flex flex-column flex-shrink-0 bg-dark text-white" id="mainSidebar" style="width: 250px; min-height: 100vh;">
    <div class="sidebar-header p-3 border-bottom border-secondary d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-database text-se2026"></i>
            <div>
                <h6 class="mb-0 text-white">SE2026</h6>
                <small class="text-secondary">Dashboard Monitoring</small>
            </div>
        </div>
        <button class="sidebar-toggle d-md-none text-white" id="sidebarCloseBtn" type="button" aria-label="Tutup menu">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="p-3 border-bottom border-secondary">
        <div class="d-flex align-items-center gap-2 text-white">
            <i class="fas fa-user-circle fs-5"></i>
            <div class="small">
                <div class="fw-semibold"><?= htmlspecialchars($current_user['username'] ?? 'User') ?></div>
                <span class="badge bg-<?= $role === 'admin' ? 'danger' : ($role === 'pml' ? 'warning text-dark' : ($role === 'pcl' ? 'success' : ($role === 'task_force' ? 'info' : 'primary'))) ?> rounded-pill">
                    <?= htmlspecialchars(ROLE_LABELS[$role] ?? $role) ?>
                </span>
            </div>
        </div>
    </div>

    <nav class="flex-grow-1 p-3">
        <ul class="nav flex-column">
            <li class="nav-item mb-1">
                <a class="nav-link text-white <?= $page === 'dashboard' && $sub === '' ? 'active bg-se2026 rounded' : '' ?>" href="?page=dashboard">
                    <i class="fas fa-home me-2"></i><span>Beranda</span>
                </a>
            </li>

            <?php if ($isAdmin): ?>
            <li class="nav-item mb-1">
                <a class="nav-link text-white <?= $page === 'dashboard' && $sub === 'import' ? 'active bg-se2026 rounded' : '' ?>" href="?page=dashboard&sub=import">
                    <i class="fas fa-file-import me-2"></i><span>Import SIPW</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($isAdmin || $role === 'pegawai'): ?>
            <li class="nav-item mb-1">
                <a class="nav-link text-white <?= $page === 'dashboard' && $sub === 'assignment' ? 'active bg-se2026 rounded' : '' ?>" href="?page=dashboard&sub=assignment">
                    <i class="fas fa-tasks me-2"></i><span>Assignment</span>
                </a>
            </li>
            <?php endif; ?>

            <li class="nav-item mb-1">
                <a class="nav-link text-white <?= $page === 'dashboard' && $sub === 'monitoring' ? 'active bg-se2026 rounded' : '' ?>" href="?page=dashboard&sub=monitoring">
                    <i class="fas fa-chart-bar me-2"></i><span>Monitoring</span>
                </a>
            </li>

            <?php if ($isAdmin || $role === 'pegawai'): ?>
            <li class="nav-item mb-1">
                <a class="nav-link text-white <?= $page === 'dashboard' && $sub === 'workload' ? 'active bg-se2026 rounded' : '' ?>" href="?page=dashboard&sub=workload">
                    <i class="fas fa-weight me-2"></i><span>Beban Kerja</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($isAdmin || $role === 'task_force'): ?>
            <li class="nav-item mb-1">
                <a class="nav-link text-white <?= $page === 'dashboard' && $sub === 'wilayah' ? 'active bg-se2026 rounded' : '' ?>" href="?page=dashboard&sub=wilayah">
                    <i class="fas fa-map-marker-alt me-2"></i><span>Wilayah</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($role === 'admin'): ?>
            <li class="nav-item mb-1">
                <a class="nav-link text-white <?= $page === 'dashboard' && $sub === 'petugas-lapangan' ? 'active bg-se2026 rounded' : '' ?>" href="?page=dashboard&sub=petugas-lapangan">
                    <i class="fas fa-people-carry-box me-2"></i><span>PCL / PML / TF</span>
                </a>
            </li>
            <li class="nav-item mb-1">
                <a class="nav-link text-white <?= $page === 'dashboard' && $sub === 'petugas' ? 'active bg-se2026 rounded' : '' ?>" href="?page=dashboard&sub=petugas">
                    <i class="fas fa-users me-2"></i><span>Petugas</span>
                </a>
            </li>
            <li class="nav-item mb-1">
                <a class="nav-link text-white <?= $page === 'dashboard' && $sub === 'pml-report' ? 'active bg-se2026 rounded' : '' ?>" href="?page=dashboard&sub=pml-report">
                    <i class="fas fa-clipboard-list me-2"></i><span>Laporan PML</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($isAdmin || $role === 'pml'): ?>
            <li class="nav-item mb-1">
                <a class="nav-link text-white <?= $page === 'dashboard' && $sub === 'pml-report' ? 'active bg-se2026 rounded' : '' ?>" href="?page=dashboard&sub=pml-report">
                    <i class="fas fa-clipboard-list me-2"></i><span>Laporan PML</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($isAdmin || in_array($role, ['task_force', 'pml', 'pcl'], true)): ?>
            <li class="nav-item mb-1">
                <a class="nav-link text-white <?= $page === 'dashboard' && $sub === 'insight' ? 'active bg-se2026 rounded' : '' ?>" href="?page=dashboard&sub=insight">
                    <i class="fas fa-chart-line me-2"></i><span>Insight &amp; Analisa</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
            <li class="nav-item mb-1">
                <a class="nav-link text-white <?= $page === 'dashboard' && $sub === 'pegawai-activity' ? 'active bg-se2026 rounded' : '' ?>" href="?page=dashboard&sub=pegawai-activity">
                    <i class="fas fa-user-clock me-2"></i><span>Aktivitas Pegawai</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
            <li class="nav-item mb-1">
                <a class="nav-link text-white <?= $page === 'dashboard' && $sub === 'audit' ? 'active bg-se2026 rounded' : '' ?>" href="?page=dashboard&sub=audit">
                    <i class="fas fa-history me-2"></i><span>Audit Log</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-footer p-3 border-top border-secondary">
        <div class="d-grid">
            <a href="?page=logout" class="btn btn-outline-light btn-sm">
                <i class="fas fa-sign-out-alt me-1"></i>Logout
            </a>
        </div>
        <small class="text-secondary d-block text-center mt-2">&copy; BPS Jember 2026</small>
    </div>
</aside>
