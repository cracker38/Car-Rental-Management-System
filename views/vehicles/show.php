<div class="row g-3 mb-3">
  <div class="col-lg-6">
    <div class="card h-100 overflow-hidden">
      <img class="w-100" style="max-height:420px;object-fit:cover" src="<?= e(vehicle_img($vehicle['image_path'] ?? null, $vehicle['category_name'] ?? null)) ?>" alt="<?= e($vehicle['make'] . ' ' . $vehicle['model']) ?>">
    </div>
  </div>
  <div class="col-lg-6">
    <section class="card card-body h-100">
      <p class="eyebrow mb-1"><?= e($vehicle['category_name']) ?></p>
      <h2 class="h4"><?= e($vehicle['make'] . ' ' . $vehicle['model']) ?><?php if (!empty($vehicle['manufacture_year'])): ?> <span class="text-muted"><?= (int)$vehicle['manufacture_year'] ?></span><?php endif; ?></h2>
      <p class="text-muted"><?= e($vehicle['description'] ?: 'A well maintained vehicle, ready for your next trip.') ?></p>
      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge text-bg-light"><?= e(ucfirst($vehicle['transmission'])) ?> transmission</span>
        <span class="badge text-bg-light"><?= e(ucfirst(str_replace('_', ' ', $vehicle['fuel_type']))) ?></span>
        <?php if (!\Crms\Auth::isCustomer()): ?><span class="badge text-bg-secondary"><?= e($vehicle['registration_no']) ?></span><?php endif; ?>
      </div>
      <div class="row g-2 mb-3">
        <div class="col-sm-4"><div class="p-3 bg-light rounded"><small class="d-block text-muted">Daily rate</small><strong><?= e(\Crms\Money::format($vehicle['daily_rate'])) ?></strong></div></div>
        <?php if (!empty($vehicle['weekly_rate'])): ?><div class="col-sm-4"><div class="p-3 bg-light rounded"><small class="d-block text-muted">Weekly rate</small><strong><?= e(\Crms\Money::format($vehicle['weekly_rate'])) ?></strong></div></div><?php endif; ?>
        <?php if (!empty($vehicle['monthly_rate'])): ?><div class="col-sm-4"><div class="p-3 bg-light rounded"><small class="d-block text-muted">Monthly rate</small><strong><?= e(\Crms\Money::format($vehicle['monthly_rate'])) ?></strong></div></div><?php endif; ?>
      </div>
      <?php if (!\Crms\Auth::isCustomer()): ?><p class="mb-3">Status: <span class="badge text-bg-secondary"><?= e($vehicle['status']) ?></span> &middot; Odometer <?= (int)$vehicle['current_mileage'] ?> km</p><?php endif; ?>
      <?php if (\Crms\Auth::isCustomer()): ?>
        <div class="mt-auto d-flex gap-2 flex-wrap"><a class="btn btn-primary" href="<?= e(url('search', ['category_id' => $vehicle['category_id']])) ?>">Check dates and availability</a><a class="btn btn-outline-dark" href="<?= e(url('reservations/form', ['vehicle_id' => $vehicle['vehicle_id']])) ?>">Request this vehicle</a></div>
      <?php endif; ?>
      <?php if ((can('inspections.manage') || can('maintenance.manage')) && !in_array($vehicle['status'], ['inactive','maintenance'], true)): ?>
        <form method="post" action="<?= e(url('vehicles/flag', ['id' => $vehicle['vehicle_id']])) ?>" class="mt-3" data-confirm="Flag this vehicle as unavailable for hire?">
          <?= \Crms\Csrf::field() ?>
          <label class="form-label">Flag as unsafe / unavailable</label>
          <div class="input-group">
            <input class="form-control" name="flag_note" maxlength="250" placeholder="Safety reason" required>
            <button class="btn btn-outline-dark">Flag vehicle</button>
          </div>
        </form>
      <?php elseif ($vehicle['status'] === 'maintenance'): ?>
        <p class="small text-warning-emphasis mt-3 mb-0">This vehicle is unavailable for hire while unsafe or open maintenance is being resolved.</p>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php if (!\Crms\Auth::isCustomer()): ?>
  <h3 class="h6">Recent reservations</h3>
  <div class="table-responsive card"><table class="table mb-0">
    <thead><tr><th>Code</th><th>Pickup</th><th>Return</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($history as $h): ?>
      <tr><td><?= e($h['reservation_code']) ?></td><td><?= e($h['pickup_datetime']) ?></td><td><?= e($h['return_datetime']) ?></td><td><?= e($h['status']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
<?php endif; ?>
