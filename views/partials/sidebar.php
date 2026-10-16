<?php
$role = $user['role_name'] ?? '';
$isCustomer = $role === 'Customer';
$isDriver = $role === 'Driver';
?>
<aside class="sidebar d-none d-lg-flex">
  <a class="brand" href="<?= e(url($user ? 'dashboard' : 'home')) ?>">
    <span class="brand-mark"><i class="bi bi-car-front-fill" aria-hidden="true"></i></span>
    <span>Rent Car Rwanda</span>
  </a>
  <?php if (!empty($user)): ?>
    <div class="sidebar-role"><?= e($user['first_name']) ?><span><?= e($role) ?></span></div>
  <?php endif; ?>
  <nav class="nav flex-column">
    <?php require __DIR__ . '/sidebar-links.php'; ?>
  </nav>
</aside>
<div class="offcanvas offcanvas-start app-offcanvas d-lg-none" tabindex="-1" id="appSidebar">
  <div class="offcanvas-header">
    <h2 class="h6 mb-0">Menu</h2>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body sidebar">
    <a class="brand" href="<?= e(url($user ? 'dashboard' : 'home')) ?>">
      <span class="brand-mark"><i class="bi bi-car-front-fill" aria-hidden="true"></i></span>
      <span>Rent Car Rwanda</span>
    </a>
    <?php if (!empty($user)): ?>
      <div class="sidebar-role"><?= e($user['first_name']) ?><span><?= e($role) ?></span></div>
    <?php endif; ?>
    <nav class="nav flex-column">
      <?php require __DIR__ . '/sidebar-links.php'; ?>
    </nav>
  </div>
</div>
