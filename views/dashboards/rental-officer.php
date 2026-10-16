<?php
$dashboardSubtitle = 'Review booking requests and coordinate today’s vehicle handovers.';
$dashboardActions = [
  ['route' => 'reservations/form', 'label' => 'New reservation', 'icon' => 'bi-plus-circle'],
  ['route' => 'reservations', 'label' => 'All reservations', 'icon' => 'bi-calendar3'],
  ['route' => 'drivers', 'label' => 'Drivers', 'icon' => 'bi-person-badge'],
];
require dirname(__DIR__) . '/partials/dashboard-heading.php';
require dirname(__DIR__) . '/partials/kpis.php';
?>
<div class="row g-4">
  <div class="col-xl-6">
    <section class="card card-body h-100">
      <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Bookings to review</h2><a href="<?= e(url('reservations')) ?>">All reservations</a></div>
      <?php if (!$pending): ?><p class="text-muted mb-0">No bookings are waiting for confirmation.</p><?php else: ?>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
          <thead><tr><th>Booking</th><th>Customer</th><th>Vehicle</th><th>Pickup</th></tr></thead>
          <tbody><?php foreach ($pending as $item): ?><tr>
            <td><a href="<?= e(url('reservations/show', ['id' => $item['reservation_id']])) ?>"><?= e($item['reservation_code']) ?></a></td>
            <td><?= e($item['first_name'] . ' ' . $item['last_name']) ?></td><td><?= e($item['registration_no']) ?></td><td><?= e($item['pickup_datetime']) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-xl-6">
    <section class="card card-body h-100">
      <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Today’s pickups</h2><a href="<?= e(url('rentals')) ?>">Rental handovers</a></div>
      <?php if (!$today): ?><p class="text-muted mb-0">No confirmed pickups are scheduled today.</p><?php else: ?>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
          <thead><tr><th>Time</th><th>Customer</th><th>Vehicle</th><th>Booking</th></tr></thead>
          <tbody><?php foreach ($today as $item): ?><tr>
            <td><?= e($item['pickup_datetime']) ?></td><td><?= e($item['first_name'] . ' ' . $item['last_name']) ?></td><td><?= e($item['registration_no']) ?></td>
            <td><a href="<?= e(url('reservations/show', ['id' => $item['reservation_id']])) ?>"><?= e($item['reservation_code']) ?></a></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php require dirname(__DIR__) . '/partials/dashboard-notifications.php'; ?>
