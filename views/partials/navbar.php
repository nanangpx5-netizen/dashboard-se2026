<nav class="navbar navbar-expand navbar-dark bg-primary px-3 shadow-sm">
    <div class="container-fluid">
        <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Buka menu">
            <i class="fas fa-bars text-white"></i>
        </button>
        <span class="navbar-brand mb-0 h6">
            <i class="fas fa-chart-line me-2"></i><?= APP_NAME ?>
        </span>
        <ul class="navbar-nav ms-auto align-items-center">
            <?php if (!empty($current_user)): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle fs-5"></i>
                        <span class="small"><?= htmlspecialchars($current_user['username'] ?? 'User') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><span class="dropdown-item-text small text-muted"><?= htmlspecialchars($current_user['role'] ?? '') ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="?page=logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
