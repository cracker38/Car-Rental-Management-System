<?php
$dashboardSubtitle = 'Business Performance — KPIs, outstanding invoices, returns, fleet utilisation, and officer activity.';
$dashboardActions = [
  ['route' => 'reports', 'label' => 'Reports', 'icon' => 'bi-graph-up'],
  ['route' => 'reports', 'label' => 'Export CSV', 'icon' => 'bi-download', 'query' => ['export' => 'csv']],
  ['route' => 'rentals', 'label' => 'Rentals', 'icon' => 'bi-key'],
];
require dirname(__DIR__) . '/partials/dashboard-heading.php';
require dirname(__DIR__) . '/partials/kpis.php';
require dirname(__DIR__) . '/partials/dashboard-shortcuts.php';
?>
<div class="row g-3">
  <div class="col-lg-4">
    <section class="panel h-100">
      <div class="panel-h">Financial snapshot</div>
      <div class="mini-row d-flex justify-content-between"><span>Payments received</span><strong><?= e(\Crms\Money::format($payments)) ?></strong></div>
      <div class="mini-row d-flex justify-content-between"><span>Refunds issued</span><strong><?= e(\Crms\Money::format($refunds)) ?></strong></div>
      <?php foreach ($fleet as $f): ?>
        <div class="mini-row d-flex justify-content-between"><span><?= e(ucfirst((string)$f['status'])) ?> vehicles</span><strong><?= (int)$f['c'] ?></strong></div>
      <?php endforeach; ?>
      <?php if (!empty($officers)): ?>
        <p class="small text-muted mt-3 mb-1">Officer activity (7 days)</p>
        <?php foreach ($officers as $officer): ?>
          <div class="mini-row d-flex justify-content-between"><span><?= e($officer['first_name'] . ' ' . $officer['last_name']) ?></span><strong><?= (int)$officer['c'] ?></strong></div>
        <?php endforeach; ?>
      <?php endif; ?>
      <?php if (!empty($maintAlerts)): ?>
        <p class="small text-muted mt-3 mb-1">Maintenance alerts</p>
        <?php foreach ($maintAlerts as $alert): ?>
          <div class="mini-row"><strong><?= e($alert['registration_no']) ?></strong><span><?= e($alert['title']) ?> · <?= e($alert['status']) ?></span></div>
        <?php endforeach; ?>
      <?php endif; ?>
      <a class="btn btn-sm btn-outline-dark mt-3" href="<?= e(url('reports')) ?>">Open reports</a>
    </section>
  </div>
  <div class="col-lg-8">
    <section class="panel table-panel">
      <div class="panel-h d-flex justify-content-between">Upcoming returns <a class="small fw-normal" href="<?= e(url('rentals')) ?>">All rentals</a></div>
      <?php if (!$due): ?><p class="text-muted mb-0">No rentals are currently due back.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th>Rental</th><th>Customer</th><th>Vehicle</th><th>Expected return</th><th>Status</th></tr></thead>
          <tbody><?php foreach ($due as $item): ?><tr>
            <td><a href="<?= e(url('rentals/show', ['id' => $item['rental_id']])) ?>"><?= e($item['rental_number']) ?></a></td>
            <td><?= e($item['first_name'] . ' ' . $item['last_name']) ?></td>
            <td><?= e($item['registration_no']) ?></td><td><?= e($item['expected_return_datetime']) ?></td><td><?= status_badge((string)$item['status']) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-12">
    <section class="panel table-panel">
      <div class="panel-h">Bookings waiting for confirmation</div>
      <?php if (!$pending): ?><p class="text-muted mb-0">The reservation queue is clear.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th>Booking</th><th>Customer</th><th>Vehicle</th><th>Pickup</th></tr></thead>
          <tbody><?php foreach ($pending as $item): ?><tr>
            <td><a href="<?= e(url('reservations/show', ['id' => $item['reservation_id']])) ?>"><?= e($item['reservation_code']) ?></a></td>
            <td><?= e($item['first_name'] . ' ' . $item['last_name']) ?></td>
            <td><?= e($item['registration_no']) ?></td><td><?= e($item['pickup_datetime']) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php require dirname(__DIR__) . '/partials/dashboard-notifications.php'; ?>
