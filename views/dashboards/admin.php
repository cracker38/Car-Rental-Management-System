<div class="role-dashboard role-dashboard-admin">
<div class="admin-dashboard-heading">
  <div>
    <p class="eyebrow mb-1">System Administrator</p>
    <h1>System Overview</h1>
    <p class="text-muted mb-0">Manage access and configuration, review system records, and monitor activity.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(url('admin/backup')) ?>"><i class="bi bi-hdd-stack me-1" aria-hidden="true"></i>Backup &amp; recovery</a>
</div>
<?php require dirname(__DIR__) . '/partials/kpis.php'; ?>
<div class="row g-3 admin-overview-panels">
  <div class="col-lg-5">
    <section class="panel h-100">
      <div class="admin-panel-heading">
        <div><h2 class="panel-h mb-1">System records</h2><p class="small text-muted mb-0">Read-only operational records</p></div>
        <a class="small fw-bold" href="<?= e(url('reports')) ?>">System reports <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </div>
      <div class="admin-record-list">
        <?php foreach ($records as $record): ?>
          <a class="admin-record-link" href="<?= e(url($record['route'])) ?>">
            <span class="admin-record-icon"><i class="bi <?= e($record['icon']) ?>" aria-hidden="true"></i></span>
            <span class="admin-record-name"><?= e($record['label']) ?></span>
            <strong><?= number_format((int)$record['value']) ?></strong>
            <i class="bi bi-chevron-right admin-record-chevron" aria-hidden="true"></i>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
  <div class="col-lg-3">
    <section class="panel h-100">
      <div class="admin-panel-heading">
        <div><h2 class="panel-h mb-1">Latest users</h2><p class="small text-muted mb-0">Recently created accounts</p></div>
        <a class="admin-panel-icon-link" href="<?= e(url('admin/users')) ?>" title="Manage users" aria-label="Manage users"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
      </div>
      <?php if (empty($users)): ?><p class="text-muted mb-0">No user accounts yet.</p><?php endif; ?>
      <?php foreach ($users as $u): ?>
        <div class="admin-user-row">
          <span class="admin-user-avatar"><i class="bi bi-person-fill" aria-hidden="true"></i></span>
          <span class="admin-user-detail"><strong><?= e($u['first_name'] . ' ' . $u['last_name']) ?></strong><small><?= e($u['email']) ?></small></span>
          <span class="admin-user-status"><?= e($u['status']) ?></span>
        </div>
      <?php endforeach; ?>
      <a class="btn btn-sm btn-outline-dark w-100 mt-3" href="<?= e(url('admin/users')) ?>">Manage user accounts</a>
    </section>
  </div>
  <div class="col-lg-4">
    <section class="panel h-100">
      <div class="admin-panel-heading">
        <div><h2 class="panel-h mb-1">Audit activity</h2><p class="small text-muted mb-0">Security events and account access</p></div>
        <a class="admin-panel-icon-link" href="<?= e(url('admin/audit')) ?>" title="Review audit logs" aria-label="Review audit logs"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
      </div>
      <?php if (empty($audit)): ?><p class="text-muted mb-0">No activity recorded yet.</p><?php endif; ?>
      <?php foreach ($audit as $event): ?>
        <div class="admin-audit-row">
          <span class="admin-audit-icon"><i class="bi <?= $event['result'] === 'success' ? 'bi-shield-check' : 'bi-shield-exclamation' ?>" aria-hidden="true"></i></span>
          <span class="admin-audit-detail"><strong><?= e($event['action']) ?></strong><small><?= e($event['created_at']) ?> · <?= e($event['result']) ?></small></span>
        </div>
      <?php endforeach; ?>
      <a class="btn btn-sm btn-outline-dark w-100 mt-3" href="<?= e(url('admin/audit')) ?>">Open audit logs</a>
    </section>
  </div>
</div>
<section class="panel mt-3">
  <div class="admin-panel-heading">
    <div><h2 class="panel-h mb-1">Fleet snapshot</h2><p class="small text-muted mb-0">Recently updated vehicles and their current status</p></div>
    <a class="small fw-bold" href="<?= e(url('vehicles')) ?>">Open fleet <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
  </div>
  <?php if (empty($recentVehicles)): ?><p class="text-muted mb-0">No vehicles are recorded yet.</p><?php else: ?>
    <div class="row g-3">
      <?php foreach ($recentVehicles as $vehicle): ?>
        <div class="col-6 col-md-3">
          <a class="dashboard-fleet-card d-block text-decoration-none" href="<?= e(url('vehicles/show', ['id' => $vehicle['vehicle_id']])) ?>">
            <img src="<?= e(vehicle_img($vehicle['image_path'] ?? null, $vehicle['category_name'] ?? null)) ?>" alt="<?= e($vehicle['make'] . ' ' . $vehicle['model']) ?>">
            <div class="dashboard-fleet-copy"><strong><?= e($vehicle['make'] . ' ' . $vehicle['model']) ?></strong><small><?= e($vehicle['registration_no']) ?> &middot; <?= e(ucfirst($vehicle['status'])) ?></small></div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/partials/dashboard-notifications.php'; ?>
</div>
