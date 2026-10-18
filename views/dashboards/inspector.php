<?php
$dashboardSubtitle = 'Fleet Inspection and Maintenance — condition photos, servicing, and unsafe-vehicle flags.';
$dashboardActions = [
  ['route' => 'rentals', 'label' => 'Return inspections', 'icon' => 'bi-clipboard-check'],
  ['route' => 'maintenance', 'label' => 'Maintenance', 'icon' => 'bi-wrench-adjustable'],
  ['route' => 'vehicles', 'label' => 'Fleet', 'icon' => 'bi-car-front'],
];
require dirname(__DIR__) . '/partials/dashboard-heading.php';
require dirname(__DIR__) . '/partials/kpis.php';
require dirname(__DIR__) . '/partials/dashboard-shortcuts.php';
?>
<div class="row g-3">
  <div class="col-lg-6">
    <section class="panel table-panel h-100">
      <div class="panel-h">Returned vehicles awaiting inspection</div>
      <?php if (!$returns): ?><p class="text-muted mb-0">No returned vehicles are waiting.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th>Rental</th><th>Vehicle</th><th>Returned</th><th></th></tr></thead>
          <tbody><?php foreach ($returns as $item): ?><tr>
            <td><?= e($item['rental_number']) ?></td><td><?= e($item['registration_no'] . ' · ' . $item['make'] . ' ' . $item['model']) ?></td>
            <td><?= e($item['actual_return_datetime'] ?? '') ?></td><td><a class="btn btn-sm btn-primary" href="<?= e(url('rentals/show', ['id' => $item['rental_id']])) ?>">Inspect</a></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-lg-6">
    <section class="panel table-panel h-100">
      <div class="panel-h">Open maintenance</div>
      <?php if (!$maint): ?><p class="text-muted mb-0">No open maintenance jobs.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th>Vehicle</th><th>Job</th><th>Start</th><th>Status</th></tr></thead>
          <tbody><?php foreach ($maint as $item): ?><tr>
            <td><?= e($item['registration_no']) ?></td><td><?= e($item['title']) ?><small class="d-block text-muted"><?= e($item['maintenance_number']) ?></small></td>
            <td><?= e($item['start_date']) ?></td><td><?= status_badge((string)$item['status']) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-12">
    <section class="panel table-panel">
      <div class="panel-h">Inspection certificates due in 14 days</div>
      <?php if (empty($dueChecks)): ?><p class="text-muted mb-0">No certificates expire in the next two weeks.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th>Vehicle</th><th>Model</th><th>Expiry</th><th></th></tr></thead>
          <tbody><?php foreach ($dueChecks as $item): ?><tr>
            <td><?= e($item['registration_no']) ?></td>
            <td><?= e($item['make'] . ' ' . $item['model']) ?></td>
            <td><?= e($item['inspection_expiry_date']) ?></td>
            <td><a class="btn btn-sm btn-outline-dark" href="<?= e(url('vehicles/show', ['id' => $item['vehicle_id']])) ?>">Open</a></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-12">
    <section class="panel table-panel">
      <div class="panel-h">Next service dates</div>
      <?php if (empty($nextService)): ?><p class="text-muted mb-0">No services fall due in the next three weeks.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th>Vehicle</th><th>Job</th><th>Next service</th></tr></thead>
          <tbody><?php foreach ($nextService as $item): ?><tr>
            <td><?= e($item['registration_no']) ?></td>
            <td><?= e($item['title']) ?></td>
            <td><?= e($item['next_service_date']) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php require dirname(__DIR__) . '/partials/dashboard-notifications.php'; ?>
