<form method="get" class="card card-body mb-4"><input type="hidden" name="r" value="reports"><div class="row g-3 align-items-end"><div class="col-sm-5"><label class="form-label" for="report-from">From</label><input class="form-control" id="report-from" name="date_from" type="date" required value="<?= e($dateFrom) ?>"></div><div class="col-sm-5"><label class="form-label" for="report-to">To</label><input class="form-control" id="report-to" name="date_to" type="date" required value="<?= e($dateTo) ?>"></div><div class="col-sm-2 d-grid"><button class="btn btn-accent">Apply dates</button></div></div></form>
<p class="small text-muted">Reporting period: <?= e($dateFrom) ?> through <?= e($dateTo) ?></p>
<?php if ($fin): ?>
  <div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card card-body h-100"><span class="text-muted">Completed payments</span><strong class="fs-4 mt-1"><?= e(\Crms\Money::format($fin['payments'])) ?></strong></div></div>
    <div class="col-md-4"><div class="card card-body h-100"><span class="text-muted">Refunds</span><strong class="fs-4 mt-1"><?= e(\Crms\Money::format($fin['refunds'])) ?></strong></div></div>
    <div class="col-md-4"><div class="card card-body h-100"><span class="text-muted">Deposits received</span><strong class="fs-4 mt-1"><?= e(\Crms\Money::format($fin['deposits'])) ?></strong></div></div>
  </div>
<?php endif; ?>
<?php if (can('reports.operational')): ?>
  <section class="card table-responsive"><div class="p-3 pb-0"><h2 class="h5">Fleet activity</h2><p class="small text-muted">Bookings and rentals with pickup dates in the selected period.</p></div><table class="table mb-0"><thead><tr><th>Vehicle</th><th>Current status</th><th>Bookings</th><th>Rentals</th></tr></thead><tbody>
  <?php if (!$ops): ?><tr><td colspan="4" class="text-muted">No vehicles are recorded.</td></tr><?php endif; ?>
  <?php foreach ($ops as $o): ?><tr><td><?= e($o['registration_no']) ?></td><td><?= status_badge($o['status']) ?></td><td><?= (int)$o['confirmed_bookings'] ?></td><td><?= (int)$o['live_rentals'] ?></td></tr><?php endforeach; ?>
  </tbody></table></section>
<?php endif; ?>
