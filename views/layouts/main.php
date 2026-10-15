<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'CRMS') ?> · Car Rental MS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= e(asset('public/css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <a class="brand" href="<?= e(url('dashboard')) ?>">CRMS Rwanda</a>
    <nav class="nav flex-column">
      <a class="nav-link" href="<?= e(url('dashboard')) ?>">Dashboard</a>
      <a class="nav-link" href="<?= e(url('search')) ?>">Availability</a>
      <?php if (can('vehicles.view')): ?><a class="nav-link" href="<?= e(url('vehicles')) ?>">Vehicles</a><?php endif; ?>
      <?php if (can('customers.view') && !\Crms\Auth::isCustomer()): ?><a class="nav-link" href="<?= e(url('customers')) ?>">Customers</a><?php endif; ?>
      <?php if (can('reservations.view')): ?><a class="nav-link" href="<?= e(url('reservations')) ?>">Reservations</a><?php endif; ?>
      <?php if (can('rentals.manage')): ?><a class="nav-link" href="<?= e(url('rentals')) ?>">Rentals</a><?php endif; ?>
      <?php if (can('invoices.view')): ?><a class="nav-link" href="<?= e(url('invoices')) ?>">Invoices</a><?php endif; ?>
      <?php if (can('transactions.manage')): ?><a class="nav-link" href="<?= e(url('finance')) ?>">Ledger</a><?php endif; ?>
      <?php if (can('maintenance.manage')): ?><a class="nav-link" href="<?= e(url('maintenance')) ?>">Maintenance</a><?php endif; ?>
      <?php if (can('reports.operational') || can('reports.financial')): ?><a class="nav-link" href="<?= e(url('reports')) ?>">Reports</a><?php endif; ?>
      <?php if (can('users.manage')): ?><a class="nav-link" href="<?= e(url('admin/users')) ?>">Users</a><?php endif; ?>
      <?php if (can('settings.manage')): ?><a class="nav-link" href="<?= e(url('admin/settings')) ?>">Settings</a><?php endif; ?>
      <?php if (can('audit.view')): ?><a class="nav-link" href="<?= e(url('admin/audit')) ?>">Audit</a><?php endif; ?>
      <?php if (can('settings.manage')): ?><a class="nav-link" href="<?= e(url('admin/backup')) ?>">Backup</a><?php endif; ?>
    </nav>
  </aside>
  <div class="main">
    <header class="topbar">
      <div>
        <h1 class="h5 mb-0"><?= e($title ?? 'CRMS') ?></h1>
        <div class="small text-muted">Currency RWF · Africa/Kigali</div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <?php if (!empty($user)): ?>
          <span class="small"><?= e($user['first_name'] . ' ' . $user['last_name']) ?> · <?= e($user['role_name']) ?></span>
          <form method="post" action="<?= e(url('logout')) ?>" class="d-inline"><?= \Crms\Csrf::field() ?>
            <button class="btn btn-sm btn-outline-secondary">Sign out</button>
          </form>
        <?php endif; ?>
      </div>
    </header>
    <main class="content">
      <?php foreach ($flashes as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
      <?php endforeach; ?>
      <?= $content ?>
    </main>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('public/js/app.js')) ?>"></script>
</body>
</html>
