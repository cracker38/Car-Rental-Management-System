<form method="get" class="panel mb-4">
  <input type="hidden" name="r" value="reports">
  <div class="panel-h">Reporting period</div>
  <div class="row g-3 align-items-end">
    <div class="col-sm-5"><label class="form-label" for="report-from">From</label><input class="form-control" id="report-from" name="date_from" type="date" required value="<?= e($dateFrom) ?>"></div>
    <div class="col-sm-5"><label class="form-label" for="report-to">To</label><input class="form-control" id="report-to" name="date_to" type="date" required value="<?= e($dateTo) ?>"></div>
    <div class="col-sm-2 d-grid"><button class="btn btn-primary">Apply dates</button></div>
  </div>
</form>
<?php if ($fin): ?>
  <div class="row g-3 mb-4">
    <div class="col-md-4"><div class="kpi-card"><div class="kpi-label">Completed payments</div><div class="kpi-value"><?= e(\Crms\Money::format($fin['payments'])) ?></div></div></div>
    <div class="col-md-4"><div class="kpi-card"><div class="kpi-label">Refunds</div><div class="kpi-value"><?= e(\Crms\Money::format($fin['refunds'])) ?></div></div></div>
    <div class="col-md-4"><div class="kpi-card"><div class="kpi-label">Deposits received</div><div class="kpi-value"><?= e(\Crms\Money::format($fin['deposits'])) ?></div></div></div>
  </div>
<?php endif; ?>
<?php if (can('reports.operational')): ?>
  <section class="panel table-panel">
    <div class="panel-h">Fleet activity</div>
    <p class="small text-muted">Bookings and rentals with pickup dates in the selected period.</p>
    <div class="table-responsive"><table class="table mb-0">
      <thead><tr><th>Vehicle</th><th>Current status</th><th>Bookings</th><th>Rentals</th></tr></thead>
      <tbody>
      <?php if (!$ops): ?><tr><td colspan="4"><div class="empty-state">No vehicles are recorded.</div></td></tr><?php endif; ?>
      <?php foreach ($ops as $o): ?><tr><td><?= e($o['registration_no']) ?></td><td><?= status_badge($o['status']) ?></td><td><?= (int)$o['confirmed_bookings'] ?></td><td><?= (int)$o['live_rentals'] ?></td></tr><?php endforeach; ?>
      </tbody>
    </table></div>
  </section>
<?php endif; ?>
