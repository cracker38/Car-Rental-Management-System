<?php
$dashboardSubtitle = 'Monitor fleet performance, active hires, returns, and business results.';
$dashboardActions = [
  ['route' => 'reports', 'label' => 'Reports', 'icon' => 'bi-graph-up'],
  ['route' => 'reservations', 'label' => 'Reservations', 'icon' => 'bi-calendar3'],
  ['route' => 'rentals', 'label' => 'Rentals', 'icon' => 'bi-key'],
];
require dirname(__DIR__) . '/partials/dashboard-heading.php';
require dirname(__DIR__) . '/partials/kpis.php';
?>
<div class="row g-4">
  <div class="col-lg-4">
    <section class="card card-body h-100">
      <h2 class="h5">Financial snapshot</h2>
      <div class="d-flex justify-content-between border-bottom py-2"><span>Payments received</span><strong><?= e(\Crms\Money::format($payments)) ?></strong></div>
      <div class="d-flex justify-content-between py-2"><span>Refunds issued</span><strong><?= e(\Crms\Money::format($refunds)) ?></strong></div>
      <a class="btn btn-sm btn-outline-dark mt-auto" href="<?= e(url('reports')) ?>">Open business reports</a>
    </section>
  </div>
  <div class="col-lg-8">
    <section class="card card-body">
      <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Upcoming returns</h2><a href="<?= e(url('rentals')) ?>">All rentals</a></div>
      <?php if (!$due): ?><p class="text-muted mb-0">No rentals are currently due back.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th>Rental</th><th>Customer</th><th>Vehicle</th><th>Expected return</th><th>Status</th></tr></thead>
          <tbody><?php foreach ($due as $item): ?><tr>
            <td><?= e($item['rental_number']) ?></td><td><?= e($item['first_name'] . ' ' . $item['last_name']) ?></td>
            <td><?= e($item['registration_no']) ?></td><td><?= e($item['expected_return_datetime']) ?></td><td><?= status_badge((string)$item['status']) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php require dirname(__DIR__) . '/partials/dashboard-notifications.php'; ?>
