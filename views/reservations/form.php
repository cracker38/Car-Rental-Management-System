<form method="post" class="card card-body">
  <?= \Crms\Csrf::field() ?>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Customer</label>
      <select class="form-select" name="customer_id" required <?= \Crms\Auth::isCustomer() ? 'disabled' : '' ?>>
        <?php foreach ($customers as $c): ?>
          <option value="<?= (int)$c['customer_id'] ?>"><?= e($c['customer_code'] . ' · ' . $c['first_name'] . ' ' . $c['last_name']) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (\Crms\Auth::isCustomer()): ?><input type="hidden" name="customer_id" value="<?= (int)($customers[0]['customer_id'] ?? 0) ?>"><?php endif; ?>
    </div>
    <div class="col-md-6"><label class="form-label">Vehicle</label>
      <select class="form-select" name="vehicle_id" required>
        <?php foreach ($vehicles as $v): ?>
          <option value="<?= (int)$v['vehicle_id'] ?>" <?= (string)$preVehicle === (string)$v['vehicle_id'] ? 'selected' : '' ?>><?= e($v['registration_no'] . ' · ' . $v['make'] . ' ' . $v['model']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6"><label class="form-label">Pickup</label><input class="form-control" type="datetime-local" name="pickup_datetime" required value="<?= e(str_replace(' ', 'T', substr($pickup, 0, 16))) ?>"></div>
    <div class="col-md-6"><label class="form-label">Return</label><input class="form-control" type="datetime-local" name="return_datetime" required value="<?= e(str_replace(' ', 'T', substr($returnAt, 0, 16))) ?>"></div>
    <div class="col-md-6"><label class="form-label">Pickup location</label><input class="form-control" name="pickup_location"></div>
    <div class="col-md-6"><label class="form-label">Return location</label><input class="form-control" name="return_location"></div>
    <?php if (!\Crms\Auth::isCustomer()): ?>
      <div class="col-md-4"><label class="form-label">Discount (RWF)</label><input class="form-control" name="discount_amount" value="0"></div>
      <div class="col-12"><label class="form-label">Internal notes</label><textarea class="form-control" name="internal_notes"></textarea></div>
    <?php endif; ?>
    <div class="col-12"><label class="form-label">Customer notes</label><textarea class="form-control" name="customer_notes"></textarea></div>
  </div>
  <p class="small text-muted mt-3">Totals are calculated on the server. Adjacent bookings at the same timestamp are allowed (half-open periods). A turnaround buffer can be set in Settings.</p>
  <button class="btn btn-primary">Save reservation</button>
</form>
