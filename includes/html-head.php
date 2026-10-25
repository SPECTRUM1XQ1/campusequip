<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="app-base" content="<?= htmlspecialchars(APP_BASE) ?>">
<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
<script src="<?= APP_BASE ?>/public/assets/js/app-core.js?v=<?= @filemtime(__DIR__ . '/../public/assets/js/app-core.js') ?: 1 ?>"></script>
<title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?> — <?= APP_NAME ?></title>
<link rel="icon" type="image/svg+xml" href="<?= APP_BASE ?>/public/assets/images/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="<?= APP_BASE ?>/public/assets/images/favicon-32x32.png">
<link rel="stylesheet" href="<?= APP_BASE ?>/public/assets/vendor/phosphor/style.css?v=2.1.2">
<link rel="stylesheet" href="<?= APP_BASE ?>/public/assets/css/base.css?v=<?= @filemtime(__DIR__ . '/../public/assets/css/base.css') ?: 1 ?>">
<link rel="stylesheet" href="<?= APP_BASE ?>/public/assets/css/shared/sidebar.css?v=<?= @filemtime(__DIR__ . '/../public/assets/css/shared/sidebar.css') ?: 1 ?>">
<link rel="stylesheet" href="<?= APP_BASE ?>/public/assets/css/shared/topbar.css?v=<?= @filemtime(__DIR__ . '/../public/assets/css/shared/topbar.css') ?: 1 ?>">
<link rel="stylesheet" href="<?= APP_BASE ?>/public/assets/css/shared/snackbar.css?v=<?= @filemtime(__DIR__ . '/../public/assets/css/shared/snackbar.css') ?: 1 ?>">
<link rel="stylesheet" href="<?= APP_BASE ?>/public/assets/css/shared/modal.css?v=<?= @filemtime(__DIR__ . '/../public/assets/css/shared/modal.css') ?: 1 ?>">
<link rel="stylesheet" href="<?= APP_BASE ?>/public/assets/css/shared/motion.css?v=<?= @filemtime(__DIR__ . '/../public/assets/css/shared/motion.css') ?: 1 ?>">
<?php /* Per-page stylesheets: each page lists its own files in $pageStyles before including this head (e.g. ['pages/manage-dashboard']). */ ?>
<?php foreach (($pageStyles ?? []) as $css): ?>
<link rel="stylesheet" href="<?= APP_BASE ?>/public/assets/css/<?= $css ?>.css?v=<?= @filemtime(__DIR__ . '/../public/assets/css/' . $css . '.css') ?: 1 ?>">
<?php endforeach; ?>
