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
<?php $isSystemAdmin = ($user['role_name'] ?? '') === 'System Administrator'; ?>
<body class="site-body app-body<?= $isSystemAdmin ? ' admin-workspace' : '' ?>">
<div class="app-shell">
  <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
  <div class="app-main">
    <header class="app-topbar">
      <button class="btn btn-outline-dark d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-label="Open menu"><i class="bi bi-list"></i></button>
      <?php if ($isSystemAdmin): ?>
        <button class="btn btn-sm btn-outline-dark d-none d-lg-inline-flex admin-sidebar-toggle" type="button" data-sidebar-toggle aria-expanded="true" aria-label="Collapse navigation"><i class="bi bi-list"></i></button>
        <div class="admin-topbar-title"><p class="eyebrow mb-0">System workspace</p><strong>Platform Administration</strong></div>
        <div class="app-topbar-actions admin-topbar-actions">
          <form class="admin-topbar-search" method="get" action="<?= e(url('admin/users')) ?>">
            <input class="form-control form-control-sm" type="search" name="q" value="<?= e((string)($q ?? '')) ?>" placeholder="Search users" aria-label="Search user accounts">
            <button class="btn btn-sm btn-outline-dark" type="submit" aria-label="Search"><i class="bi bi-search" aria-hidden="true"></i></button>
          </form>
          <a class="btn btn-sm btn-outline-dark admin-topbar-icon" href="<?= e(url('admin/audit')) ?>" title="Review audit events" aria-label="Review audit events"><i class="bi bi-bell"></i></a>
          <div class="admin-user-chip"><span class="admin-user-avatar"><i class="bi bi-person-fill" aria-hidden="true"></i></span><span><strong><?= e($user['first_name'] ?? 'Admin') ?></strong><small>System Administrator</small></span></div>
          <a class="btn btn-sm btn-outline-dark admin-website-link" href="<?= e(url('home')) ?>">Website</a>
          <form method="post" action="<?= e(url('logout')) ?>"><?= \Crms\Csrf::field() ?><button class="btn btn-sm btn-dark">Sign out</button></form>
        </div>
      <?php else: ?>
        <div>
          <p class="eyebrow mb-0">Workspace</p>
          <strong><?= e($title ?? '') ?></strong>
        </div>
        <div class="app-topbar-actions">
          <a class="btn btn-sm btn-outline-dark" href="<?= e(url('home')) ?>">Website</a>
          <form method="post" action="<?= e(url('logout')) ?>"><?= \Crms\Csrf::field() ?><button class="btn btn-sm btn-dark">Sign out</button></form>
        </div>
      <?php endif; ?>
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
