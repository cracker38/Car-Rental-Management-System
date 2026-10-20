<?php
$role = $user['role_name'] ?? '';
$isCustomer = $role === 'Customer';
$isDriver = $role === 'Driver';
$isRentalOfficer = $role === 'Rental Officer';
$isInspector = $role === 'Vehicle Inspector/Maintenance Staff';
$isSystemAdmin = $role === 'System Administrator';
?>
<div class="nav-label"><?= $isSystemAdmin ? 'Platform' : 'Workspace' ?></div>
<a class="nav-link <?= nav_active('dashboard') ?>" href="<?= e(url('dashboard')) ?>"><i class="bi bi-grid-1x2"></i> <?= $isSystemAdmin ? 'Overview' : 'Dashboard' ?></a>
<?php if ($isCustomer): ?>
  <a class="nav-link <?= nav_active('search') ?>" href="<?= e(url('search')) ?>"><i class="bi bi-calendar2-check"></i> Rent a car</a>
  <a class="nav-link <?= nav_active('fleet') ?>" href="<?= e(url('fleet')) ?>"><i class="bi bi-car-front"></i> Browse fleet</a>
  <a class="nav-link <?= nav_active('reservations') ?>" href="<?= e(url('reservations')) ?>"><i class="bi bi-journal-text"></i> My trips</a>
  <a class="nav-link <?= nav_active('rentals') ?>" href="<?= e(url('rentals')) ?>"><i class="bi bi-key"></i> My rentals &amp; agreements</a>
  <a class="nav-link <?= nav_active('invoices') ?>" href="<?= e(url('invoices')) ?>"><i class="bi bi-receipt"></i> My invoices</a>
  <?php if (report_mode() !== null): ?><a class="nav-link <?= nav_active('reports') ?>" href="<?= e(url('reports')) ?>"><i class="bi bi-graph-up"></i> My reports</a><?php endif; ?>
  <a class="nav-link <?= nav_active('customers/profile') ?>" href="<?= e(url('customers/profile')) ?>"><i class="bi bi-person"></i> My profile</a>
<?php elseif ($isDriver): ?>
  <?php if (can('drivers.schedule')): ?><a class="nav-link <?= nav_active('drivers/schedule') ?>" href="<?= e(url('drivers/schedule')) ?>"><i class="bi bi-calendar2-week"></i> My schedule</a><?php endif; ?>
  <?php if (report_mode() !== null): ?><a class="nav-link <?= nav_active('reports') ?>" href="<?= e(url('reports')) ?>"><i class="bi bi-graph-up"></i> My trip report</a><?php endif; ?>
<?php elseif ($isRentalOfficer): ?>
  <a class="nav-link <?= nav_active('search') ?>" href="<?= e(url('search')) ?>"><i class="bi bi-search"></i> Find available vehicles</a>
  <?php if (can('vehicles.view')): ?><a class="nav-link <?= nav_active('vehicles') ?>" href="<?= e(url('vehicles')) ?>"><i class="bi bi-car-front"></i> Vehicles</a><?php endif; ?>
  <?php if (can('customers.view')): ?><a class="nav-link <?= nav_active('customers') ?>" href="<?= e(url('customers')) ?>"><i class="bi bi-people"></i> Customers</a><?php endif; ?>
  <?php if (can('reservations.view')): ?><a class="nav-link <?= nav_active('reservations') ?>" href="<?= e(url('reservations')) ?>"><i class="bi bi-calendar3"></i> Reservations</a><?php endif; ?>
  <?php if (can('rentals.manage') || can('rentals.view')): ?><a class="nav-link <?= nav_active('rentals') ?>" href="<?= e(url('rentals')) ?>"><i class="bi bi-key"></i> Pickups &amp; Returns</a><?php endif; ?>
  <?php if (can('maintenance.schedule') || can('maintenance.view')): ?><a class="nav-link <?= nav_active('maintenance') ?>" href="<?= e(url('maintenance')) ?>"><i class="bi bi-wrench-adjustable"></i> Vehicles in maintenance</a><?php endif; ?>
  <?php if (report_mode() !== null): ?><a class="nav-link <?= nav_active('reports') ?>" href="<?= e(url('reports')) ?>"><i class="bi bi-graph-up"></i> Rental reports</a><?php endif; ?>
<?php elseif ($isInspector): ?>
  <?php if (can('vehicles.view')): ?><a class="nav-link <?= nav_active('vehicles') ?>" href="<?= e(url('vehicles')) ?>"><i class="bi bi-car-front"></i> Vehicles in maintenance</a><?php endif; ?>
  <?php if (can('maintenance.manage') || can('maintenance.view')): ?><a class="nav-link <?= nav_active('maintenance') ?>" href="<?= e(url('maintenance')) ?>"><i class="bi bi-wrench-adjustable"></i> Maintenance &amp; readiness</a><?php endif; ?>
  <?php if (report_mode() !== null): ?><a class="nav-link <?= nav_active('reports') ?>" href="<?= e(url('reports')) ?>"><i class="bi bi-graph-up"></i> Maintenance reports</a><?php endif; ?>
<?php elseif ($isSystemAdmin): ?>
  <div class="nav-label">Administration</div>
  <?php if (can('users.manage')): ?><a class="nav-link <?= nav_active('admin/users') ?>" href="<?= e(url('admin/users')) ?>"><i class="bi bi-people"></i> Users</a><?php endif; ?>
  <?php if (can('users.manage')): ?><a class="nav-link <?= nav_active('admin/roles') ?>" href="<?= e(url('admin/roles')) ?>"><i class="bi bi-shield-lock"></i> Roles &amp; Permissions</a><?php endif; ?>
  <?php if (can('settings.manage')): ?><a class="nav-link <?= nav_active('admin/settings') ?>" href="<?= e(url('admin/settings')) ?>"><i class="bi bi-sliders"></i> Settings</a><?php endif; ?>
  <?php if (can('audit.view')): ?><a class="nav-link <?= nav_active('admin/audit') ?>" href="<?= e(url('admin/audit')) ?>"><i class="bi bi-journal-text"></i> Audit Logs</a><?php endif; ?>
  <?php if (can('settings.manage')): ?><a class="nav-link <?= nav_active('admin/backup') ?>" href="<?= e(url('admin/backup')) ?>"><i class="bi bi-hdd-stack"></i> Backup &amp; Recovery</a><?php endif; ?>
  <div class="nav-label">System records</div>
  <?php if (can('vehicles.view')): ?><a class="nav-link <?= nav_active('vehicles') ?>" href="<?= e(url('vehicles')) ?>"><i class="bi bi-car-front"></i> Vehicles</a><?php endif; ?>
  <?php if (can('reservations.view')): ?><a class="nav-link <?= nav_active('reservations') ?>" href="<?= e(url('reservations')) ?>"><i class="bi bi-calendar3"></i> Reservations</a><?php endif; ?>
  <?php if (can('rentals.view')): ?><a class="nav-link <?= nav_active('rentals') ?>" href="<?= e(url('rentals')) ?>"><i class="bi bi-key"></i> Rentals</a><?php endif; ?>
  <?php if (can('transactions.view')): ?><a class="nav-link <?= nav_active('finance') ?>" href="<?= e(url('finance')) ?>"><i class="bi bi-wallet2"></i> Payments</a><?php endif; ?>
  <?php if (can('maintenance.view')): ?><a class="nav-link <?= nav_active('maintenance') ?>" href="<?= e(url('maintenance')) ?>"><i class="bi bi-wrench-adjustable"></i> Maintenance</a><?php endif; ?>
  <?php if (can('drivers.manage')): ?><a class="nav-link <?= nav_active('drivers') ?>" href="<?= e(url('drivers')) ?>"><i class="bi bi-person-badge"></i> Drivers</a><?php endif; ?>
  <div class="nav-label">Reporting</div>
  <?php if (report_mode() !== null): ?><a class="nav-link <?= nav_active('reports') ?>" href="<?= e(url('reports')) ?>"><i class="bi bi-graph-up"></i> System Reports</a><?php endif; ?>
<?php else: ?>
  <a class="nav-link <?= nav_active('search') ?>" href="<?= e(url('search')) ?>"><i class="bi bi-search"></i> Availability</a>
  <?php if (can('vehicles.view')): ?><a class="nav-link <?= nav_active('vehicles') ?>" href="<?= e(url('vehicles')) ?>"><i class="bi bi-car-front"></i> Vehicles</a><?php endif; ?>
  <?php if (can('customers.view')): ?><a class="nav-link <?= nav_active('customers') ?>" href="<?= e(url('customers')) ?>"><i class="bi bi-people"></i> Customers</a><?php endif; ?>
  <?php if (can('reservations.view')): ?><a class="nav-link <?= nav_active('reservations') ?>" href="<?= e(url('reservations')) ?>"><i class="bi bi-calendar3"></i> Reservations</a><?php endif; ?>
  <?php if (can('rentals.manage') || can('rentals.view')): ?><a class="nav-link <?= nav_active('rentals') ?>" href="<?= e(url('rentals')) ?>"><i class="bi bi-key"></i> Rentals</a>
  <?php elseif (can('inspections.manage')): ?><a class="nav-link <?= nav_active('rentals') ?>" href="<?= e(url('rentals')) ?>"><i class="bi bi-clipboard-check"></i> Return inspections</a><?php endif; ?>
  <?php if (can('invoices.view')): ?><a class="nav-link <?= nav_active('invoices') ?>" href="<?= e(url('invoices')) ?>"><i class="bi bi-receipt"></i> Invoices</a><?php endif; ?>
  <?php if (can('transactions.manage') || can('transactions.view')): ?><a class="nav-link <?= nav_active('finance') ?>" href="<?= e(url('finance')) ?>"><i class="bi bi-wallet2"></i> Payments</a><?php endif; ?>
  <?php if (can('transactions.approve')): ?><a class="nav-link <?= nav_active('finance/approvals') ?>" href="<?= e(url('finance/approvals')) ?>"><i class="bi bi-shield-check"></i> Financial approvals</a><?php endif; ?>
  <?php if (can('maintenance.manage') || can('maintenance.schedule') || can('maintenance.view')): ?><a class="nav-link <?= nav_active('maintenance') ?>" href="<?= e(url('maintenance')) ?>"><i class="bi bi-wrench-adjustable"></i> <?= ($role === 'Business Manager') ? 'Inspector report' : 'Maintenance' ?></a><?php endif; ?>
  <?php if (can('drivers.manage')): ?><a class="nav-link <?= nav_active('drivers') ?>" href="<?= e(url('drivers')) ?>"><i class="bi bi-person-badge"></i> Drivers</a><?php endif; ?>
  <?php if (can('drivers.schedule')): ?><a class="nav-link <?= nav_active('drivers/schedule') ?>" href="<?= e(url('drivers/schedule')) ?>"><i class="bi bi-calendar2-week"></i> Driver schedule</a><?php endif; ?>
  <?php if (report_mode() !== null): ?><a class="nav-link <?= nav_active('reports') ?>" href="<?= e(url('reports')) ?>"><i class="bi bi-graph-up"></i> Reports</a><?php endif; ?>
  <?php if (can('users.manage') || can('settings.manage') || can('audit.view')): ?>
    <div class="nav-label">Administration</div>
    <?php if (can('users.manage')): ?><a class="nav-link <?= nav_active('admin/users') ?>" href="<?= e(url('admin/users')) ?>"><i class="bi bi-person-gear"></i> Users &amp; roles</a><?php endif; ?>
    <?php if (can('settings.manage')): ?><a class="nav-link <?= nav_active('admin/settings') ?>" href="<?= e(url('admin/settings')) ?>"><i class="bi bi-sliders"></i> Settings</a><?php endif; ?>
    <?php if (can('audit.view')): ?><a class="nav-link <?= nav_active('admin/audit') ?>" href="<?= e(url('admin/audit')) ?>"><i class="bi bi-shield-check"></i> Audit log</a><?php endif; ?>
    <?php if (can('settings.manage')): ?><a class="nav-link <?= nav_active('admin/backup') ?>" href="<?= e(url('admin/backup')) ?>"><i class="bi bi-hdd"></i> Backup</a><?php endif; ?>
  <?php endif; ?>
<?php endif; ?>
<div class="nav-label">Account</div>
<a class="nav-link <?= nav_active('password/change') ?>" href="<?= e(url('password/change')) ?>"><i class="bi bi-key"></i> Change password</a>
