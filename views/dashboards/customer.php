<?php
$dashboardSubtitle = 'Your bookings, invoices, and available vehicles in one place.';
$dashboardActions = [
  ['route' => 'search', 'label' => 'Find a car', 'icon' => 'bi-search'],
  ['route' => 'reservations', 'label' => 'My trips', 'icon' => 'bi-calendar3'],
  ['route' => 'invoices', 'label' => 'My invoices', 'icon' => 'bi-receipt'],
];
require dirname(__DIR__) . '/partials/dashboard-heading.php';
require dirname(__DIR__) . '/partials/kpis.php';
?>
<div class="row g-4">
  <div class="col-xl-8">
    <section class="card card-body h-100">
      <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">My recent bookings</h2><a href="<?= e(url('reservations')) ?>">All trips</a></div>
      <?php if (!$reservations): ?><p class="text-muted mb-0">You have no bookings yet. <a href="<?= e(url('search')) ?>">Find a car to get started.</a></p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th>Booking</th><th>Vehicle</th><th>Dates</th><th>Status</th></tr></thead>
          <tbody><?php foreach ($reservations as $item): ?><tr>
            <td><a href="<?= e(url('reservations/show', ['id' => $item['reservation_id']])) ?>"><?= e($item['reservation_code']) ?></a></td>
            <td><?= e($item['make'] . ' ' . $item['model']) ?></td><td><?= e($item['pickup_datetime']) ?><small class="d-block text-muted"><?= e($item['return_datetime']) ?></small></td>
            <td><?= status_badge((string)$item['status']) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-xl-4">
    <section class="card card-body h-100">
      <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">My invoices</h2><a href="<?= e(url('invoices')) ?>">All invoices</a></div>
      <?php if (!$invoices): ?><p class="text-muted mb-0">Your invoices will appear here when they are issued.</p><?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($invoices as $invoice): ?><li class="list-group-item px-0 d-flex justify-content-between gap-2">
            <span><a href="<?= e(url('invoices/show', ['id' => $invoice['invoice_id']])) ?>"><?= e($invoice['invoice_number']) ?></a><small class="d-block text-muted"><?= e($invoice['status']) ?></small></span>
            <span class="text-end"><?= e(\Crms\Money::format($invoice['outstanding_balance'] ?? 0)) ?><small class="d-block text-muted">due</small></span>
          </li><?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>
</div>
<section class="mt-4">
  <div class="d-flex justify-content-between align-items-end mb-3"><div><p class="eyebrow mb-1">Explore</p><h2 class="h5 mb-0">Available vehicles</h2></div><a href="<?= e(url('fleet')) ?>">Browse the fleet</a></div>
  <?php if (!$fleet): ?><p class="text-muted">No vehicles are available to browse right now.</p><?php else: ?>
    <div class="row g-3">
      <?php foreach ($fleet as $vehicle): ?><div class="col-md-6 col-xl-4">
        <article class="card h-100 overflow-hidden">
          <img src="<?= e(vehicle_img($vehicle['image_path'] ?? null, $vehicle['category_name'] ?? null)) ?>" alt="<?= e($vehicle['make'] . ' ' . $vehicle['model']) ?>" class="card-img-top" style="height:180px;object-fit:cover">
          <div class="card-body"><small class="text-muted"><?= e($vehicle['category_name']) ?></small><h3 class="h6 mt-1"><?= e($vehicle['make'] . ' ' . $vehicle['model']) ?></h3><p class="mb-0"><?= e(\Crms\Money::format($vehicle['daily_rate'])) ?> / day</p></div>
        </article>
      </div><?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/partials/dashboard-notifications.php'; ?>
