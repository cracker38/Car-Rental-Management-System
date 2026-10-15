<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'CRMS') ?> · Car Rental MS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= e(asset('public/css/app.css')) ?>" rel="stylesheet">
</head>
<body class="public-body">
<nav class="navbar navbar-expand-lg navbar-dark public-nav">
  <div class="container">
    <a class="navbar-brand" href="<?= e(url('home')) ?>">CRMS Rwanda</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <div class="navbar-nav ms-auto">
        <a class="nav-link" href="<?= e(url('search')) ?>">Search cars</a>
        <?php if (!empty($user)): ?>
          <a class="nav-link" href="<?= e(url('dashboard')) ?>">Dashboard</a>
        <?php else: ?>
          <a class="nav-link" href="<?= e(url('login')) ?>">Sign in</a>
          <a class="nav-link" href="<?= e(url('register')) ?>">Register</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<main class="container py-4">
  <?php foreach ($flashes as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
  <?php endforeach; ?>
  <?= $content ?>
</main>
<footer class="text-center text-muted py-4 small">Car Rental Management System · Rwanda · RWF</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('public/js/app.js')) ?>"></script>
</body>
</html>
