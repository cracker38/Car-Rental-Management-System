<?php
$dashboardSubtitle = 'Users, fleet, payments, SMS, and security for Rent Car Rwanda.';
$dashboardActions = [
  ['route' => 'admin/users', 'label' => 'Manage users', 'icon' => 'bi-people'],
  ['route' => 'admin/settings', 'label' => 'Settings', 'icon' => 'bi-sliders'],
  ['route' => 'reports', 'label' => 'Reports', 'icon' => 'bi-graph-up'],
];
require dirname(__DIR__) . '/partials/dashboard-heading.php';
require dirname(__DIR__) . '/partials/kpis.php';
require dirname(__DIR__) . '/partials/dashboard-shortcuts.php';
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="panel">
      <div class="panel-h">Fleet by status</div>
      <?php if (empty($fleet)): ?><p class="text-muted mb-0">No vehicles yet.</p><?php endif; ?>
      <?php foreach ($fleet as $f): ?>
        <div class="mini-row d-flex justify-content-between"><span><?= status_badge($f['status']) ?></span><strong><?= (int)$f['c'] ?></strong></div>
      <?php endforeach; ?>
      <a class="btn btn-sm btn-primary mt-3" href="<?= e(url('vehicles')) ?>">Manage fleet</a>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="panel">
      <div class="panel-h">Latest accounts</div>
      <?php if (empty($users)): ?><p class="text-muted mb-0">No users yet.</p><?php endif; ?>
      <?php foreach ($users as $u): ?>
        <div class="mini-row"><strong><?= e($u['first_name'] . ' ' . $u['last_name']) ?></strong><span><?= e($u['role_name']) ?> · <?= e($u['status']) ?></span></div>
      <?php endforeach; ?>
      <a class="btn btn-sm btn-outline-dark mt-3" href="<?= e(url('admin/users')) ?>">Roles &amp; access</a>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="panel">
      <div class="panel-h">Audit &amp; SMS</div>
      <?php foreach ($audit as $a): ?>
        <div class="mini-row"><strong><?= e($a['action']) ?></strong><span><?= e($a['created_at']) ?> · <?= e($a['result']) ?></span></div>
      <?php endforeach; ?>
      <?php if (!empty($smsFailed)): ?>
        <p class="small text-muted mt-3 mb-1">Failed SMS</p>
        <?php foreach ($smsFailed as $sms): ?>
          <div class="mini-row"><strong><?= e($sms['subject']) ?></strong><span><?= e($sms['error_message'] ?: 'Rejected') ?></span></div>
        <?php endforeach; ?>
      <?php endif; ?>
      <a class="btn btn-sm btn-outline-secondary mt-3" href="<?= e(url('admin/audit')) ?>">Full log</a>
    </div>
  </div>
</div>
<?php require dirname(__DIR__) . '/partials/dashboard-notifications.php'; ?>
