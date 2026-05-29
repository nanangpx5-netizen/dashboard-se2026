<?php
/** @var string $title */
$title = $title ?? 'Dashboard';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-bold"><i class="fas fa-home me-2"></i><?= htmlspecialchars($title) ?></h5>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="?page=dashboard">Dashboard</a></li>
            <?php if (!empty($page) && $page !== 'dashboard'): ?>
                <li class="breadcrumb-item active"><?= htmlspecialchars(ucfirst($page)) ?></li>
            <?php endif; ?>
        </ol>
    </nav>
</div>
