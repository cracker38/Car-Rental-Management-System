<p class="lead-muted">System overview — users, fleet health and recent security events.</p>
<?php require dirname(__DIR__) . '/partials/kpis.php'; ?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="panel">
      <div class="panel-h">Fleet by status</div>
      <ul class="list-unstyled mb-0">
        <?php foreach ($fleet as $f): ?>
          <li class="d-flex justify-content-between py-2 border-bottom"><span><?= status_badge($f['status']) ?></span><strong><?= (int)$f['c'] ?></strong></li>
        <?php endforeach; ?>
      </ul>
      <a class="btn btn-sm btn-primary mt-3" href="<?= e(url('vehicles')) ?>">Manage fleet photos &amp; rates</a>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="panel">
      <div class="panel-h">Latest accounts</div>
      <?php foreach ($users as $u): ?>
        <div class="mini-row"><strong><?= e($u['first_name'] . ' ' . $u['last_name']) ?></strong><span><?= e($u['role_name']) ?> · <?= e($u['status']) ?></span></div>
      <?php endforeach; ?>
      <a class="btn btn-sm btn-outline-primary mt-3" href="<?= e(url('admin/users')) ?>">User roles</a>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="panel">
      <div class="panel-h">Audit</div>
      <?php foreach ($audit as $a): ?>
        <div class="mini-row"><strong><?= e($a['action']) ?></strong><span><?= e($a['created_at']) ?> · <?= e($a['result']) ?></span></div>
      <?php endforeach; ?>
      <a class="btn btn-sm btn-outline-secondary mt-3" href="<?= e(url('admin/audit')) ?>">Full log</a>
    </div>
  </div>
</div>
