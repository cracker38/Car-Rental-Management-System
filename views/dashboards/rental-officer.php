<?php
$dashboardEyebrow = 'Daily rental operations';
$dashboardHeading = 'Rental Operations';
$dashboardSubtitle = 'Manage customers, vehicle availability, reservations, agreements, pickups and returns.';
$dashboardDutiesHeading = 'Daily responsibilities';
$dashboardRestrictionHeading = 'Restriction';
$dashboardNotificationHeading = 'Reservation notifications';
$dashboardActions = [
  ['route' => 'search', 'label' => 'Find a vehicle', 'icon' => 'bi-search'],
  ['route' => 'reservations/form', 'label' => 'New reservation', 'icon' => 'bi-plus-circle'],
  ['route' => 'customers/form', 'label' => 'Register customer', 'icon' => 'bi-person-plus'],
];
require dirname(__DIR__) . '/partials/dashboard-heading.php';
require dirname(__DIR__) . '/partials/kpis.php';
require dirname(__DIR__) . '/partials/dashboard-shortcuts.php';
?>
<div class="row g-3">
  <div class="col-xl-6">
    <section class="panel table-panel h-100">
      <div class="panel-h d-flex justify-content-between">Reservations to review <a class="small fw-normal" href="<?= e(url('reservations')) ?>">All reservations</a></div>
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
    <section class="panel table-panel h-100">
      <div class="panel-h">Upcoming pickups <span class="small fw-normal text-muted">Next 7 days</span></div>
      <?php if (!$upcomingPickups): ?><p class="text-muted mb-0">No confirmed pickups are scheduled in the next 7 days.</p><?php else: ?>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
          <thead><tr><th>Time</th><th>Customer</th><th>Vehicle</th><th>Booking</th></tr></thead>
          <tbody><?php foreach ($upcomingPickups as $item): ?><tr>
            <td><?= e($item['pickup_datetime']) ?></td><td><?= e($item['first_name'] . ' ' . $item['last_name']) ?></td><td><?= e($item['registration_no']) ?></td>
            <td><a href="<?= e(url('reservations/show', ['id' => $item['reservation_id']])) ?>"><?= e($item['reservation_code']) ?></a></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-12">
    <section class="panel table-panel">
      <div class="panel-h d-flex justify-content-between">Upcoming and overdue returns <a class="small fw-normal" href="<?= e(url('rentals')) ?>">All rentals</a></div>
      <?php if (empty($upcomingReturns)): ?><p class="text-muted mb-0">No returns are due in the next 7 days.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th>Rental</th><th>Customer</th><th>Vehicle</th><th>Due</th><th>Status</th></tr></thead>
          <tbody><?php foreach ($upcomingReturns as $item): ?><tr>
            <td><a href="<?= e(url('rentals/show', ['id' => $item['rental_id']])) ?>"><?= e($item['rental_number']) ?></a></td>
            <td><?= e($item['first_name'] . ' ' . $item['last_name']) ?></td>
            <td><?= e($item['registration_no']) ?></td><td><?= e($item['expected_return_datetime']) ?></td><td><?= status_badge((string) $item['display_status']) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php require dirname(__DIR__) . '/partials/dashboard-notifications.php'; ?>
