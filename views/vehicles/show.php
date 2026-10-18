<div class="card card-body mb-3">
  <h2 class="h5"><?= e($vehicle['make'] . ' ' . $vehicle['model']) ?> · <?= e($vehicle['registration_no']) ?></h2>
  <p>Status: <span class="badge text-bg-secondary"><?= e($vehicle['status']) ?></span> · Daily <?= e(\Crms\Money::format($vehicle['daily_rate'])) ?></p>
  <p class="mb-0">Odometer <?= (int)$vehicle['current_mileage'] ?> km · <?= e($vehicle['category_name']) ?></p>
  <?php if ((can('inspections.manage') || can('maintenance.manage')) && $vehicle['status'] !== 'inactive'): ?>
    <form method="post" action="<?= e(url('vehicles/flag', ['id' => $vehicle['vehicle_id']])) ?>" class="mt-3" data-confirm="Flag this vehicle as unavailable for hire?">
      <?= \Crms\Csrf::field() ?>
      <label class="form-label">Flag as unsafe / unavailable</label>
      <div class="input-group">
        <input class="form-control" name="flag_note" maxlength="250" placeholder="Reason (optional)">
        <button class="btn btn-outline-dark">Flag vehicle</button>
      </div>
    </form>
  <?php endif; ?>
</div>
<h3 class="h6">Recent reservations</h3>
<div class="table-responsive card"><table class="table mb-0">
  <thead><tr><th>Code</th><th>Pickup</th><th>Return</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($history as $h): ?>
    <tr><td><?= e($h['reservation_code']) ?></td><td><?= e($h['pickup_datetime']) ?></td><td><?= e($h['return_datetime']) ?></td><td><?= e($h['status']) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
