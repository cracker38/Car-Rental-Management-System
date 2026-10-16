<?php
$dashboardSubtitle = 'Inspect returned vehicles, track workshop work, and watch fleet checks due.';
$dashboardActions = [
  ['route' => 'rentals', 'label' => 'Return inspections', 'icon' => 'bi-clipboard-check'],
  ['route' => 'maintenance', 'label' => 'Maintenance', 'icon' => 'bi-wrench-adjustable'],
  ['route' => 'vehicles', 'label' => 'Fleet', 'icon' => 'bi-car-front'],
];
require dirname(__DIR__) . '/partials/dashboard-heading.php';
require dirname(__DIR__) . '/partials/kpis.php';
?>
<div class="row g-4">
  <div class="col-lg-6">
    <section class="card card-body h-100">
      <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Returned vehicles awaiting inspection</h2><a href="<?= e(url('rentals')) ?>">All inspections</a></div>
      <?php if (!$returns): ?><p class="text-muted mb-0">No returned vehicles are waiting for inspection.</p><?php else: ?>
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
    <section class="card card-body h-100">
      <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Open maintenance work</h2><a href="<?= e(url('maintenance')) ?>">Maintenance list</a></div>
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
</div>
<?php require dirname(__DIR__) . '/partials/dashboard-notifications.php'; ?>
