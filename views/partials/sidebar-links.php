<?php
$role = $user['role_name'] ?? '';
$isCustomer = $role === 'Customer';
$isDriver = $role === 'Driver';
?>
<div class="nav-label">Workspace</div>
<a class="nav-link <?= nav_active('dashboard') ?>" href="<?= e(url('dashboard')) ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a>
<?php if ($isCustomer): ?>
  <a class="nav-link <?= nav_active('search') ?>" href="<?= e(url('search')) ?>"><i class="bi bi-calendar2-check"></i> Book a car</a>
  <a class="nav-link <?= nav_active('fleet') ?>" href="<?= e(url('fleet')) ?>"><i class="bi bi-car-front"></i> Browse fleet</a>
  <a class="nav-link <?= nav_active('reservations') ?>" href="<?= e(url('reservations')) ?>"><i class="bi bi-journal-text"></i> My trips</a>
  <a class="nav-link <?= nav_active('invoices') ?>" href="<?= e(url('invoices')) ?>"><i class="bi bi-receipt"></i> My invoices</a>
<?php elseif ($isDriver): ?>
  <?php if (can('drivers.schedule')): ?><a class="nav-link <?= nav_active('drivers/schedule') ?>" href="<?= e(url('drivers/schedule')) ?>"><i class="bi bi-calendar2-week"></i> My schedule</a><?php endif; ?>
<?php else: ?>
  <a class="nav-link <?= nav_active('search') ?>" href="<?= e(url('search')) ?>"><i class="bi bi-search"></i> Availability</a>
  <?php if (can('vehicles.view')): ?><a class="nav-link <?= nav_active('vehicles') ?>" href="<?= e(url('vehicles')) ?>"><i class="bi bi-car-front"></i> Vehicles</a><?php endif; ?>
  <?php if (can('customers.view')): ?><a class="nav-link <?= nav_active('customers') ?>" href="<?= e(url('customers')) ?>"><i class="bi bi-people"></i> Customers</a><?php endif; ?>
  <?php if (can('reservations.view')): ?><a class="nav-link <?= nav_active('reservations') ?>" href="<?= e(url('reservations')) ?>"><i class="bi bi-calendar3"></i> Reservations</a><?php endif; ?>
  <?php if (can('rentals.manage')): ?><a class="nav-link <?= nav_active('rentals') ?>" href="<?= e(url('rentals')) ?>"><i class="bi bi-key"></i> Rentals</a><?php endif; ?>
  <?php if (can('invoices.view')): ?><a class="nav-link <?= nav_active('invoices') ?>" href="<?= e(url('invoices')) ?>"><i class="bi bi-receipt"></i> Invoices</a><?php endif; ?>
  <?php if (can('transactions.manage')): ?><a class="nav-link <?= nav_active('finance') ?>" href="<?= e(url('finance')) ?>"><i class="bi bi-wallet2"></i> Payments</a><?php endif; ?>
  <?php if (can('maintenance.manage')): ?><a class="nav-link <?= nav_active('maintenance') ?>" href="<?= e(url('maintenance')) ?>"><i class="bi bi-wrench-adjustable"></i> Maintenance</a><?php endif; ?>
  <?php if (can('drivers.manage')): ?><a class="nav-link <?= nav_active('drivers') ?>" href="<?= e(url('drivers')) ?>"><i class="bi bi-person-badge"></i> Drivers</a><?php endif; ?>
  <?php if (can('drivers.schedule')): ?><a class="nav-link <?= nav_active('drivers/schedule') ?>" href="<?= e(url('drivers/schedule')) ?>"><i class="bi bi-calendar2-week"></i> Driver schedule</a><?php endif; ?>
  <?php if (can('reports.operational') || can('reports.financial')): ?><a class="nav-link <?= nav_active('reports') ?>" href="<?= e(url('reports')) ?>"><i class="bi bi-graph-up"></i> Reports</a><?php endif; ?>
  <?php if (can('users.manage') || can('settings.manage') || can('audit.view')): ?>
    <div class="nav-label">Administration</div>
    <?php if (can('users.manage')): ?><a class="nav-link <?= nav_active('admin/users') ?>" href="<?= e(url('admin/users')) ?>"><i class="bi bi-person-gear"></i> Users &amp; roles</a><?php endif; ?>
    <?php if (can('settings.manage')): ?><a class="nav-link <?= nav_active('admin/settings') ?>" href="<?= e(url('admin/settings')) ?>"><i class="bi bi-sliders"></i> Settings</a><?php endif; ?>
    <?php if (can('audit.view')): ?><a class="nav-link <?= nav_active('admin/audit') ?>" href="<?= e(url('admin/audit')) ?>"><i class="bi bi-shield-check"></i> Audit log</a><?php endif; ?>
    <?php if (can('settings.manage')): ?><a class="nav-link <?= nav_active('admin/backup') ?>" href="<?= e(url('admin/backup')) ?>"><i class="bi bi-hdd"></i> Backup</a><?php endif; ?>
  <?php endif; ?>
<?php endif; ?>
