<form method="post" enctype="multipart/form-data" class="card card-body">
  <?= \Crms\Csrf::field() ?>
  <div class="row g-3">
    <?php if (!empty($rentalOfficer) && !empty($vehicle)): ?>
    <div class="col-md-4"><label class="form-label">Category</label><input class="form-control" value="<?= e($vehicle['category_name'] ?? '') ?>" readonly><input type="hidden" name="category_id" value="<?= (int) $vehicle['category_id'] ?>"></div>
    <?php else: ?><div class="col-md-4"><label class="form-label">Category</label>
      <select class="form-select" name="category_id" required>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int)$c['category_id'] ?>" <?= (string)($vehicle['category_id'] ?? '') === (string)$c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div><?php endif; ?>
    <div class="col-md-4"><label class="form-label">Registration</label><input class="form-control" name="registration_no" required value="<?= e($vehicle['registration_no'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">VIN</label><input class="form-control" name="vin" value="<?= e($vehicle['vin'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">Make</label><input class="form-control" name="make" required value="<?= e($vehicle['make'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">Model</label><input class="form-control" name="model" required value="<?= e($vehicle['model'] ?? '') ?>"></div>
    <div class="col-md-2"><label class="form-label">Year</label><input class="form-control" type="number" name="manufacture_year" value="<?= e((string)($vehicle['manufacture_year'] ?? '')) ?>"></div>
    <div class="col-md-2"><label class="form-label">Colour</label><input class="form-control" name="colour" value="<?= e($vehicle['colour'] ?? '') ?>"></div>
    <div class="col-md-3"><label class="form-label">Transmission</label>
      <select class="form-select" name="transmission">
        <?php foreach (['manual','automatic','other'] as $t): ?>
          <option value="<?= $t ?>" <?= ($vehicle['transmission'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3"><label class="form-label">Fuel</label>
      <select class="form-select" name="fuel_type">
        <?php foreach (['petrol','diesel','hybrid','electric','other'] as $t): ?>
          <option value="<?= $t ?>" <?= ($vehicle['fuel_type'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if (empty($rentalOfficer)): ?>
      <div class="col-md-2"><label class="form-label">Daily rate</label><input class="form-control" name="daily_rate" required value="<?= e((string)($vehicle['daily_rate'] ?? '')) ?>"></div>
      <div class="col-md-2"><label class="form-label">Weekly</label><input class="form-control" name="weekly_rate" value="<?= e((string)($vehicle['weekly_rate'] ?? '')) ?>"></div>
      <div class="col-md-2"><label class="form-label">Monthly</label><input class="form-control" name="monthly_rate" value="<?= e((string)($vehicle['monthly_rate'] ?? '')) ?>"></div>
    <?php else: ?>
      <div class="col-12"><div class="alert alert-info mb-0">Pricing is controlled by approved category rates. New vehicles use the selected category’s default rates; existing rates cannot be changed here.</div></div>
    <?php endif; ?>
    <div class="col-md-2"><label class="form-label">Mileage</label><input class="form-control" type="number" name="current_mileage" value="<?= e((string)($vehicle['current_mileage'] ?? '0')) ?>" <?= !empty($rentalOfficer) && !empty($vehicle) ? 'readonly' : '' ?>></div>
    <div class="col-md-2"><label class="form-label">Fuel %</label><input class="form-control" name="fuel_level_percent" value="<?= e((string)($vehicle['fuel_level_percent'] ?? '')) ?>" <?= !empty($rentalOfficer) && !empty($vehicle) ? 'readonly' : '' ?>></div>
    <?php if (!empty($rentalOfficer) && !empty($vehicle)): ?><div class="col-12"><div class="form-text">Mileage and fuel are updated from recorded pickup and return handovers.</div></div><?php endif; ?>
    <?php if (empty($rentalOfficer)): ?><div class="col-md-3"><label class="form-label">Status</label>
      <select class="form-select" name="status">
        <?php foreach (['available','reserved','rented','maintenance','inactive'] as $t): ?>
          <option value="<?= $t ?>" <?= ($vehicle['status'] ?? 'available') === $t ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
      </select>
    </div><?php endif; ?>
    <div class="col-md-3"><label class="form-label">Insurance expiry</label><input class="form-control" type="date" name="insurance_expiry_date" value="<?= e($vehicle['insurance_expiry_date'] ?? '') ?>"></div>
    <div class="col-md-3"><label class="form-label">Inspection expiry</label><input class="form-control" type="date" name="inspection_expiry_date" value="<?= e($vehicle['inspection_expiry_date'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label">Photo</label><input class="form-control" type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>
    <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description"><?= e($vehicle['description'] ?? '') ?></textarea></div>
  </div>
  <button class="btn btn-primary mt-3">Save</button>
</form>
