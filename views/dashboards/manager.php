<div class="dashboard-heading manager-dashboard-heading">
  <div>
    <p class="eyebrow mb-1">Business oversight</p>
    <h1>Business Performance</h1>
    <p class="text-muted mb-0">Revenue, bookings, active rentals, fleet utilization, and operating trends.</p>
  </div>
  <div class="dashboard-heading-actions">
    <a class="btn btn-primary" href="<?= e(url('reports')) ?>"><i class="bi bi-graph-up" aria-hidden="true"></i>Business reports</a>
    <a class="btn btn-outline-dark" href="<?= e(url('reports', ['export' => 'csv'])) ?>"><i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i>Export Excel (CSV)</a>
    <a class="btn btn-outline-dark" href="<?= e(url('reports') . '#report-exports') ?>"><i class="bi bi-printer" aria-hidden="true"></i>Print / PDF</a>
  </div>
</div>
<?php require dirname(__DIR__) . '/partials/dashboard-duties.php'; ?>
<?php $kpiColClass = count($kpis) >= 6 ? 'col-6 col-md-4 col-xl-2' : 'col-6 col-lg-3'; require dirname(__DIR__) . '/partials/kpis.php'; ?>
<?php require dirname(__DIR__) . '/partials/dashboard-shortcuts.php'; ?>

<section class="panel table-panel mb-3">
  <div class="panel-h d-flex flex-wrap align-items-baseline justify-content-between gap-2">
    <span>Pending reservation approvals</span>
    <a class="small fw-normal" href="<?= e(url('reservations')) ?>">All reservations</a>
  </div>
  <?php if (!$pendingReservations): ?><p class="text-muted mb-0">No reservations are waiting for a booking decision.</p><?php else: ?>
    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
      <thead><tr><th scope="col">Booking</th><th scope="col">Customer</th><th scope="col">Vehicle</th><th scope="col">Pickup</th><th scope="col"></th></tr></thead>
      <tbody><?php foreach ($pendingReservations as $item): ?><tr>
        <td><a href="<?= e(url('reservations/show', ['id' => $item['reservation_id']])) ?>"><?= e($item['reservation_code']) ?></a></td>
        <td><?= e($item['first_name'] . ' ' . $item['last_name']) ?></td>
        <td><?= e($item['registration_no']) ?></td>
        <td><?= e($item['pickup_datetime']) ?></td>
        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('reservations/show', ['id' => $item['reservation_id']])) ?>">Review &amp; decide</a></td>
      </tr><?php endforeach; ?></tbody>
    </table></div>
  <?php endif; ?>
</section>

<?php
$maxBookings = max(1, ...array_column($trends, 'bookings'));
$maxRentals = max(1, ...array_column($trends, 'rentals'));
$maxRevenue = max(1, ...array_column($trends, 'revenue'));
?>
<section class="panel table-panel mb-3">
  <div class="panel-h d-flex flex-wrap align-items-baseline justify-content-between gap-2">
    <span>Business trends</span>
    <span class="small fw-normal text-muted">Monthly performance over the last six months</span>
  </div>
  <div class="table-responsive">
    <table class="table manager-trend-table align-middle mb-0">
      <thead><tr><th scope="col">Month</th><th scope="col">Bookings</th><th scope="col">Rental pickups</th><?php if ($canViewFinancial): ?><th scope="col">Revenue</th><?php endif; ?></tr></thead>
      <tbody>
      <?php foreach ($trends as $trend): ?>
        <?php
          $bookingWidth = (int) round(((int) $trend['bookings'] / $maxBookings) * 100);
          $rentalWidth = (int) round(((int) $trend['rentals'] / $maxRentals) * 100);
          $revenueWidth = (int) round(((float) $trend['revenue'] / $maxRevenue) * 100);
        ?>
        <tr>
          <th scope="row" class="text-nowrap"><?= e($trend['label']) ?></th>
          <td><strong><?= number_format((int) $trend['bookings']) ?></strong><div class="progress manager-trend-progress" role="img" aria-label="Bookings trend: <?= number_format((int) $trend['bookings']) ?>"><div class="progress-bar" style="width: <?= $bookingWidth ?>%"></div></div></td>
          <td><strong><?= number_format((int) $trend['rentals']) ?></strong><div class="progress manager-trend-progress" role="img" aria-label="Rental pickups trend: <?= number_format((int) $trend['rentals']) ?>"><div class="progress-bar" style="width: <?= $rentalWidth ?>%"></div></div></td>
          <?php if ($canViewFinancial): ?><td><strong><?= e(\Crms\Money::format($trend['revenue'])) ?></strong><div class="progress manager-trend-progress" role="img" aria-label="Revenue trend: <?= e(\Crms\Money::format($trend['revenue'])) ?>"><div class="progress-bar" style="width: <?= $revenueWidth ?>%"></div></div></td><?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <section class="panel table-panel h-100">
      <div class="panel-h d-flex justify-content-between gap-2"><span>Upcoming and overdue returns</span><a class="small fw-normal" href="<?= e(url('rentals')) ?>">All rentals</a></div>
      <?php if (!$due): ?><p class="text-muted mb-0">No returns are overdue or due within the next seven days.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th scope="col">Rental</th><th scope="col">Customer</th><th scope="col">Vehicle</th><th scope="col">Expected return</th><th scope="col">Status</th></tr></thead>
          <tbody><?php foreach ($due as $item): ?><tr>
            <td><a href="<?= e(url('rentals/show', ['id' => $item['rental_id']])) ?>"><?= e($item['rental_number']) ?></a></td>
            <td><?= e($item['first_name'] . ' ' . $item['last_name']) ?></td>
            <td><?= e($item['registration_no']) ?></td><td class="text-nowrap"><?= e($item['expected_return_datetime']) ?></td><td><?= status_badge((string) $item['status']) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-lg-4">
    <section class="panel h-100">
      <div class="panel-h d-flex justify-content-between gap-2"><span>Fleet availability</span><a class="small fw-normal" href="<?= e(url('vehicles')) ?>">Fleet</a></div>
      <?php if (!$fleet): ?><p class="text-muted mb-0">No vehicles are recorded.</p><?php endif; ?>
      <?php foreach ($fleet as $item): ?>
        <div class="mini-row d-flex justify-content-between align-items-center"><span><?= status_badge((string) $item['status']) ?></span><strong><?= number_format((int) $item['c']) ?></strong></div>
      <?php endforeach; ?>
      <div class="manager-subsection-heading">Maintenance alerts <a class="small fw-normal" href="<?= e(url('maintenance')) ?>">View records</a></div>
      <?php if (!$maintAlerts): ?><p class="small text-muted mb-0">No scheduled or in-progress maintenance alerts.</p><?php else: ?>
        <?php foreach ($maintAlerts as $alert): ?><div class="mini-row"><strong><?= e($alert['registration_no']) ?></strong><span><?= e($alert['title']) ?> &middot; <?= e(ucwords(str_replace('_', ' ', $alert['status']))) ?></span></div><?php endforeach; ?>
      <?php endif; ?>
    </section>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <section class="panel h-100">
      <div class="panel-h">Reservation and rental performance</div>
      <div class="row g-3">
        <div class="col-md-6">
          <h3 class="manager-subsection-heading mt-0">Reservations</h3>
          <?php if (!$reservationPerformance): ?><p class="small text-muted mb-0">No reservation records yet.</p><?php endif; ?>
          <?php foreach ($reservationPerformance as $item): ?><div class="mini-row d-flex justify-content-between align-items-center"><span><?= status_badge((string) $item['status']) ?></span><strong><?= number_format((int) $item['c']) ?></strong></div><?php endforeach; ?>
        </div>
        <div class="col-md-6">
          <h3 class="manager-subsection-heading mt-0">Rentals</h3>
          <?php if (!$rentalPerformance): ?><p class="small text-muted mb-0">No rental records yet.</p><?php endif; ?>
          <?php foreach ($rentalPerformance as $item): ?><div class="mini-row d-flex justify-content-between align-items-center"><span><?= status_badge((string) $item['status']) ?></span><strong><?= number_format((int) $item['c']) ?></strong></div><?php endforeach; ?>
        </div>
      </div>
    </section>
  </div>
  <div class="col-lg-5">
    <section class="panel h-100">
      <div class="panel-h d-flex justify-content-between gap-2"><span>Rental officer activity</span><span class="small fw-normal text-muted">Last 7 days</span></div>
      <?php if (!$officers): ?><p class="text-muted mb-0">No rental officers are recorded.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th scope="col">Officer</th><th scope="col">Bookings</th><th scope="col">Rentals</th></tr></thead>
          <tbody><?php foreach ($officers as $officer): ?><tr>
            <td><?= e($officer['first_name'] . ' ' . $officer['last_name']) ?></td>
            <td><?= number_format((int) $officer['bookings']) ?></td>
            <td><?= number_format((int) $officer['rentals']) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
</div>
