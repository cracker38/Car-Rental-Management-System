<?php
$dashboardSubtitle = 'Your chauffeur assignments, pickup times, and customer contacts.';
$dashboardActions = [
  ['route' => 'drivers/schedule', 'label' => 'Full schedule', 'icon' => 'bi-calendar2-week'],
];
require dirname(__DIR__) . '/partials/dashboard-heading.php';
require dirname(__DIR__) . '/partials/kpis.php';
require dirname(__DIR__) . '/partials/dashboard-shortcuts.php';
?>
<?php if (!empty($nextTrip)): ?>
<section class="panel invoice-hero mb-3">
  <p class="eyebrow">Next pickup</p>
  <h2><?= e($nextTrip['reservation_code']) ?></h2>
  <div class="kpi-row">
    <div><span>When</span><strong><?= e($nextTrip['pickup_datetime']) ?></strong></div>
    <div><span>Customer</span><strong><?= e($nextTrip['first_name'] . ' ' . $nextTrip['last_name']) ?></strong></div>
    <div><span>Vehicle</span><strong><?= e($nextTrip['registration_no']) ?></strong></div>
    <div><span>Route</span><strong><?= e($nextTrip['pickup_location'] ?: 'TBC') ?></strong></div>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <?php if (!empty($nextTrip['phone'])): ?>
      <a class="btn btn-primary" href="tel:<?= e($nextTrip['phone']) ?>"><i class="bi bi-telephone"></i> Call customer</a>
      <a class="btn btn-outline-dark" href="https://wa.me/<?= e(preg_replace('/\D+/', '', (string)$nextTrip['phone'])) ?>" target="_blank" rel="noopener">WhatsApp</a>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>
<section class="panel table-panel">
  <div class="panel-h d-flex justify-content-between">My upcoming trips <a class="small fw-normal" href="<?= e(url('drivers/schedule')) ?>">Open schedule</a></div>
  <?php if (!$assignments): ?><p class="text-muted mb-0">There are no upcoming trips assigned to your driver account.</p><?php else: ?>
    <div class="table-responsive"><table class="table align-middle mb-0">
      <thead><tr><th>Booking</th><th>Pickup</th><th>Customer</th><th>Vehicle</th><th>Route</th><th>Status</th></tr></thead>
      <tbody><?php foreach ($assignments as $trip): ?><tr>
        <td><?= e($trip['reservation_code']) ?></td><td><?= e($trip['pickup_datetime']) ?></td>
        <td><?= e($trip['first_name'] . ' ' . $trip['last_name']) ?><a class="d-block" href="tel:<?= e($trip['phone']) ?>"><?= e($trip['phone']) ?></a></td>
        <td><?= e($trip['make'] . ' ' . $trip['model']) ?><small class="d-block text-muted"><?= e($trip['registration_no']) ?></small></td>
        <td><?= e($trip['pickup_location'] ?: 'Pickup location pending') ?><span class="d-block text-muted">to <?= e($trip['return_location'] ?: 'Return location pending') ?></span></td>
        <td><?= status_badge((string)$trip['reservation_status']) ?></td>
      </tr><?php endforeach; ?></tbody>
    </table></div>
  <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/partials/dashboard-notifications.php'; ?>
