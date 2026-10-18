<?php
$dashboardSubtitle = 'Vehicle condition inspections, servicing records, and safety holds.';
require dirname(__DIR__) . '/partials/dashboard-heading.php';
require dirname(__DIR__) . '/partials/kpis.php';
require dirname(__DIR__) . '/partials/dashboard-shortcuts.php';
?>
<div class="row g-3">
  <div class="col-lg-6">
    <section class="panel table-panel h-100">
      <div class="panel-h">Upcoming and recent pickup inspections</div>
      <?php if (empty($pickupChecks)): ?><p class="text-muted mb-0">No upcoming or recent pickup inspections need review.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th>Rental</th><th>Vehicle</th><th>Pickup</th><th>Status</th><th></th></tr></thead>
          <tbody><?php foreach ($pickupChecks as $item): ?><tr>
            <td><?= e($item['rental_number']) ?></td>
            <td><?= e($item['registration_no'] . ' · ' . $item['make'] . ' ' . $item['model']) ?></td>
            <td><?= e($item['pickup_datetime']) ?></td><td><?= status_badge((string)$item['status']) ?></td>
            <td><a class="btn btn-sm btn-primary" href="<?= e(url('rentals/show', ['id' => $item['rental_id']])) ?>">Inspect</a></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-lg-6">
    <section class="panel table-panel h-100">
      <div class="panel-h">Returned vehicles awaiting inspection</div>
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
  <div class="col-12">
    <section class="panel table-panel">
      <div class="panel-h">Open servicing and maintenance</div>
      <?php if (!$maint): ?><p class="text-muted mb-0">No open maintenance jobs.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th>Vehicle</th><th>Job</th><th>Start</th><th>Expected completion</th><th>Status</th><th></th></tr></thead>
          <tbody><?php foreach ($maint as $item): ?><tr>
            <td><?= e($item['registration_no']) ?></td><td><?= e($item['title']) ?><small class="d-block text-muted"><?= e($item['maintenance_number']) ?></small></td>
            <td><?= e($item['start_date']) ?></td>
            <td><?php if (!empty($item['expected_completion_date'])): ?><?= e($item['expected_completion_date']) ?><?php if ($item['expected_completion_date'] < date('Y-m-d')): ?> <span class="badge text-bg-danger">Overdue</span><?php endif; ?><?php else: ?>—<?php endif; ?></td>
            <td><?= status_badge((string)$item['status']) ?></td><td><a class="btn btn-sm btn-outline-dark" href="<?= e(url('maintenance')) ?>">Open</a></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-lg-6">
    <section class="panel table-panel h-100">
      <div class="panel-h">Upcoming servicing and overdue services</div>
      <?php if (empty($serviceSchedule)): ?><p class="text-muted mb-0">No service dates are due in the next 30 days.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th>Vehicle</th><th>Last service</th><th>Next service</th><th>Status</th></tr></thead>
          <tbody><?php foreach ($serviceSchedule as $item): ?><tr>
            <td><?= e($item['registration_no']) ?></td><td><?= e($item['title']) ?></td><td><?= e($item['next_service_date']) ?></td>
            <td><?= $item['due_state'] === 'overdue' ? '<span class="badge text-bg-danger">Overdue</span>' : '<span class="badge text-bg-info">Upcoming</span>' ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-lg-6">
    <section class="panel table-panel h-100">
      <div class="panel-h">Vehicle inspection certificates due within 14 days</div>
      <?php if (empty($dueChecks)): ?><p class="text-muted mb-0">No vehicle certificates are due in the next two weeks.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th>Vehicle</th><th>Model</th><th>Expiry</th><th></th></tr></thead>
          <tbody><?php foreach ($dueChecks as $item): ?><tr>
            <td><?= e($item['registration_no']) ?></td><td><?= e($item['make'] . ' ' . $item['model']) ?></td>
            <td><?= e($item['inspection_expiry_date']) ?></td>
            <td><a class="btn btn-sm btn-outline-dark" href="<?= e(url('vehicles/show', ['id' => $item['vehicle_id']])) ?>">Open</a></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
</div>
