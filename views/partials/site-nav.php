<?php
$current = (string) ($_GET['r'] ?? 'home');
$isHome = $current === 'home';
if (!function_exists('crms_nav_link')) {
    function crms_nav_link(string $route, string $label, string $current): void
    {
        $active = $current === $route || str_starts_with($current, $route . '/') ? ' active' : '';
        echo '<a class="nav-link' . $active . '" href="' . e(url($route)) . '">' . e($label) . '</a>';
    }
}
?>
<nav class="navbar navbar-expand-lg site-nav<?= $isHome ? ' home-nav' : '' ?>">
  <div class="container">
    <a class="navbar-brand" href="<?= e(url('home')) ?>" aria-label="Rent Car Rwanda home">
      <span class="brand-mark"><i class="bi bi-car-front-fill" aria-hidden="true"></i></span>
      <span class="brand-wordmark"><strong>Rent Car</strong><small>RWANDA</small></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav" aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="siteNav">
      <div class="navbar-nav mx-lg-auto">
        <?php if ($isHome): ?>
          <a class="nav-link active" href="#home">Home</a>
          <a class="nav-link" href="<?= e(url('about')) ?>">About</a>
          <a class="nav-link" href="<?= e(url('services')) ?>">Services</a>
          <a class="nav-link" href="#booking">Hire a car</a>
          <a class="nav-link" href="<?= e(url('blog')) ?>">Blog</a>
          <a class="nav-link" href="<?= e(url('contact')) ?>">Contact</a>
        <?php else: ?>
          <?php crms_nav_link('home', 'Home', $current); ?>
          <?php crms_nav_link('about', 'About', $current); ?>
          <a class="nav-link<?= $current === 'services' ? ' active' : '' ?>" href="<?= e(url('services')) ?>">Services</a>
          <a class="nav-link<?= $current === 'search' ? ' active' : '' ?>" href="<?= e(url('search')) ?>">Hire a car</a>
          <a class="nav-link<?= $current === 'blog' ? ' active' : '' ?>" href="<?= e(url('blog')) ?>">Blog</a>
          <?php crms_nav_link('contact', 'Contact', $current); ?>
        <?php endif; ?>
      </div>
      <div class="navbar-nav align-items-lg-center gap-lg-1">
        <?php if (!empty($user)): ?>
          <?php if (\Crms\Auth::isCustomer() || can('reservations.view')): ?>
            <a class="nav-link<?= $current === 'reservations' || str_starts_with($current, 'reservations/') ? ' active' : '' ?>" href="<?= e(url('reservations')) ?>">My trips</a>
          <?php endif; ?>
          <?php if (can('invoices.view')): ?>
            <a class="nav-link<?= $current === 'invoices' || str_starts_with($current, 'invoices/') ? ' active' : '' ?>" href="<?= e(url('invoices')) ?>">Invoices</a>
          <?php endif; ?>
          <?php
            $ops = [];
            if (can('vehicles.view') && !\Crms\Auth::isCustomer()) $ops[] = ['vehicles', 'Vehicles'];
            if (can('customers.view') && !\Crms\Auth::isCustomer()) $ops[] = ['customers', 'Customers'];
            if (can('rentals.manage')) $ops[] = ['rentals', 'Rentals'];
            if (can('transactions.manage')) $ops[] = ['finance', 'Payments'];
            if (can('maintenance.manage')) $ops[] = ['maintenance', 'Maintenance'];
            if (can('reports.operational') || can('reports.financial')) $ops[] = ['reports', 'Reports'];
            if (can('users.manage')) $ops[] = ['admin/users', 'Users'];
            if (can('settings.manage')) $ops[] = ['admin/settings', 'Settings'];
            if (can('audit.view')) $ops[] = ['admin/audit', 'Audit'];
            if (can('settings.manage')) $ops[] = ['admin/backup', 'Backup'];
          ?>
          <?php if ($ops): ?>
            <div class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Operations</a>
              <ul class="dropdown-menu dropdown-menu-end">
                <?php foreach ($ops as [$r, $lab]): ?>
                  <li><a class="dropdown-item" href="<?= e(url($r)) ?>"><?= e($lab) ?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
          <form method="post" action="<?= e(url('logout')) ?>" class="ms-lg-2"><?= \Crms\Csrf::field() ?>
            <button class="btn btn-sm btn-outline-light">Sign out</button>
          </form>
        <?php else: ?>
          <a class="nav-link" href="<?= e(url('login')) ?>">Sign in</a>
          <a class="btn btn-sm btn-accent nav-register" href="<?= e(url('register')) ?>">Get started</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
