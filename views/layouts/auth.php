<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <?= \App\Helpers\Asset::css('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', 'bootstrap/5.3.3/css/bootstrap.min.css') ?>
    <?= \App\Helpers\Asset::css('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', 'font-awesome/6.5.1/css/all.min.css') ?>
    <link href="<?= BASE_URL ?>assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <?= $content ?? '' ?>
    </div>
    <?= \App\Helpers\Asset::js('https://code.jquery.com/jquery-3.7.1.min.js', 'jquery/3.7.1/jquery.min.js') ?>
    <?= \App\Helpers\Asset::js('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', 'bootstrap/5.3.3/js/bootstrap.bundle.min.js') ?>
</body>
</html>
