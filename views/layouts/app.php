<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#141618">
  <title><?= e($title ?? 'Workspace') ?> &middot; Rent Car Rwanda</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= e(asset('public/css/app.css')) ?>" rel="stylesheet">
</head>
<body class="site-body app-body">
<div class="app-shell">
  <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
  <div class="app-main">
    <header class="app-topbar">
      <button class="btn btn-outline-dark d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-label="Open menu"><i class="bi bi-list"></i></button>
      <div>
        <p class="eyebrow mb-0">Workspace</p>
        <strong><?= e($title ?? '') ?></strong>
      </div>
      <div class="app-topbar-actions">
        <a class="btn btn-sm btn-outline-dark" href="<?= e(url('home')) ?>">Website</a>
        <form method="post" action="<?= e(url('logout')) ?>"><?= \Crms\Csrf::field() ?><button class="btn btn-sm btn-dark">Sign out</button></form>
      </div>
    </header>
    <div class="app-content">
      <?php foreach ($flashes as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
      <?php endforeach; ?>
      <?= $content ?>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('public/js/app.js')) ?>"></script>
</body>
</html>
