<?php
/** @var string $title */
/** @var string $message */
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<link rel="stylesheet" href="<?= e(asset_url('css/style.css')) ?>">
</head>
<body>
<main class="container">
  <section class="card error-card">
    <h1><?= e($title) ?></h1>
    <p><?= e($message) ?></p>
    <p><a class="btn" href="<?= e(app_url('/')) ?>">Kembali ke Beranda</a></p>
  </section>
</main>
</body>
</html>
