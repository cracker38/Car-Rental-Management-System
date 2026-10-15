<?php $reservation = $reservation ?? null; $editing = $reservation !== null; ?>
<form method="post" action="<?= e(url('reservations/form', $editing ? ['id' => $reservation['reservation_id']] : [])) ?>" class="card card-body">
  <?= \Crms\Csrf::field() ?>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label" for="reservation-customer">Customer</label>
      <select class="form-select" id="reservation-customer" name="customer_id" required <?= \Crms\Auth::isCustomer() || $editing ? 'disabled' : '' ?>>
        <?php foreach ($customers as $c): ?>
          <option value="<?= (int)$c['customer_id'] ?>" <?= ($editing && (int)$reservation['customer_id'] === (int)$c['customer_id']) || (\Crms\Auth::isCustomer() && (int)($customers[0]['customer_id'] ?? 0) === (int)$c['customer_id']) ? 'selected' : '' ?>><?= e($c['customer_code'] . ' · ' . $c['first_name'] . ' ' . $c['last_name']) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (\Crms\Auth::isCustomer() || $editing): ?><input type="hidden" name="customer_id" value="<?= (int)($reservation['customer_id'] ?? $customers[0]['customer_id'] ?? 0) ?>"><?php endif; ?>
    </div>
    <div class="col-md-6"><label class="form-label" for="reservation-vehicle">Vehicle</label>
      <select class="form-select" id="reservation-vehicle" name="vehicle_id" required>
        <?php foreach ($vehicles as $v): ?>
          <option value="<?= (int)$v['vehicle_id'] ?>" <?= (string)$preVehicle === (string)$v['vehicle_id'] ? 'selected' : '' ?>><?= e($v['registration_no'] . ' · ' . $v['make'] . ' ' . $v['model']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6"><label class="form-label" for="reservation-pickup">Pickup</label><input class="form-control" id="reservation-pickup" type="datetime-local" name="pickup_datetime" required value="<?= e(str_replace(' ', 'T', substr($pickup, 0, 16))) ?>"></div>
    <div class="col-md-6"><label class="form-label" for="reservation-return">Return</label><input class="form-control" id="reservation-return" type="datetime-local" name="return_datetime" required value="<?= e(str_replace(' ', 'T', substr($returnAt, 0, 16))) ?>"></div>
    <div class="col-md-6"><label class="form-label" for="reservation-pickup-location">Pickup location</label><input class="form-control" id="reservation-pickup-location" name="pickup_location" value="<?= e($reservation['pickup_location'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label" for="reservation-return-location">Return location</label><input class="form-control" id="reservation-return-location" name="return_location" value="<?= e($reservation['return_location'] ?? '') ?>"></div>
    <?php if (!\Crms\Auth::isCustomer()): ?>
      <div class="col-md-4"><label class="form-label" for="reservation-discount">Discount (RWF)</label><input class="form-control" id="reservation-discount" type="number" min="0" step="100" name="discount_amount" value="<?= e($reservation['discount_amount'] ?? '0') ?>"></div>
      <div class="col-12"><label class="form-label" for="reservation-internal-notes">Internal notes</label><textarea class="form-control" id="reservation-internal-notes" name="internal_notes"><?= e($reservation['internal_notes'] ?? '') ?></textarea></div>
    <?php endif; ?>
    <div class="col-12"><label class="form-label" for="reservation-customer-notes">Customer notes</label><textarea class="form-control" id="reservation-customer-notes" name="customer_notes"><?= e($reservation['customer_notes'] ?? '') ?></textarea></div>
  </div>
  <p class="small text-muted mt-3">Totals are calculated on the server. Availability is checked again when you save.</p>
  <div class="d-flex gap-2 mt-2"><button class="btn btn-primary"><?= $editing ? 'Update reservation' : 'Save reservation' ?></button><?php if ($editing): ?><a class="btn btn-outline-secondary" href="<?= e(url('reservations/show', ['id' => $reservation['reservation_id']])) ?>">Cancel</a><?php endif; ?></div>
</form>
