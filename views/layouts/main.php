<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? 'Dashboard SE2026') ?> — <?= e(APP_NAME) ?></title>
    <?= \App\Helpers\Asset::css('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', 'bootstrap/5.3.3/css/bootstrap.min.css') ?>
    <?= \App\Helpers\Asset::css('https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css', 'datatables/1.13.6/css/dataTables.bootstrap5.min.css') ?>
    <?= \App\Helpers\Asset::css('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', 'font-awesome/6.5.1/css/all.min.css') ?>
    <?= \App\Helpers\Asset::css('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', 'leaflet/1.9.4/css/leaflet.min.css') ?>
    <?= \App\Helpers\Asset::css('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', 'select2/4.1.0-rc.0/css/select2.min.css') ?>
    <?= \App\Helpers\Asset::css('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2-bootstrap-5-theme.min.css', 'select2/4.1.0-rc.0/css/select2-bootstrap-5-theme.min.css') ?>
    <link href="<?= BASE_URL ?>assets/css/app.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex min-vh-100">
        <?php require VIEW_PATH . '/partials/sidebar.php'; ?>

        <div class="d-flex flex-column flex-grow-1">
            <?php require VIEW_PATH . '/partials/navbar.php'; ?>

            <main class="flex-grow-1 p-4 bg-light">
                <?php if (!empty($flash)): ?>
                    <?php foreach ($flash as $type => $message): ?>
                        <div class="alert alert-<?= $type === 'error' ? 'danger' : ($type === 'success' ? 'success' : ($type === 'warning' ? 'warning' : 'info')) ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?= $content ?? '' ?>
            </main>

            <?php require VIEW_PATH . '/partials/footer.php'; ?>
        </div>
    </div>

    <?= \App\Helpers\Asset::js('https://code.jquery.com/jquery-3.7.1.min.js', 'jquery/3.7.1/jquery.min.js') ?>
    <?= \App\Helpers\Asset::js('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', 'bootstrap/5.3.3/js/bootstrap.bundle.min.js') ?>
    <?= \App\Helpers\Asset::js('https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', 'datatables/1.13.6/js/jquery.dataTables.min.js') ?>
    <?= \App\Helpers\Asset::js('https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js', 'datatables/1.13.6/js/dataTables.bootstrap5.min.js') ?>
    <?= \App\Helpers\Asset::js('https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', 'chartjs/4.4.1/chart.umd.min.js') ?>
    <?= \App\Helpers\Asset::js('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', 'leaflet/1.9.4/js/leaflet.min.js') ?>
    <?= \App\Helpers\Asset::js('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', 'select2/4.1.0-rc.0/js/select2.min.js') ?>
    <script src="<?= BASE_URL ?>assets/js/app.js"></script>
    <?php if (!empty($js)): ?>
        <?php foreach ((array) $js as $script): ?>
            <script src="<?= BASE_URL ?>assets/js/<?= $script ?>.js"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
