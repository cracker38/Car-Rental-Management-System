<?php
$dashboardSubtitle = 'Your upcoming chauffeur assignments and trip details.';
$dashboardActions = [
  ['route' => 'drivers/schedule', 'label' => 'Full schedule', 'icon' => 'bi-calendar2-week'],
];
require dirname(__DIR__) . '/partials/dashboard-heading.php';
require dirname(__DIR__) . '/partials/kpis.php';
?>
<section class="card card-body">
  <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">My upcoming trips</h2><a href="<?= e(url('drivers/schedule')) ?>">Open schedule</a></div>
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
