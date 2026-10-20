<?php $reservation = $reservation ?? null; $editing = $reservation !== null; $rentalOfficer = (\Crms\Auth::user()['role_name'] ?? '') === 'Rental Officer'; $customerSigning = \Crms\Auth::isCustomer(); ?>
<form method="post" action="<?= e(url('reservations/form', $editing ? ['id' => $reservation['reservation_id']] : [])) ?>" class="card card-body">
  <?= \Crms\Csrf::field() ?>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label" for="reservation-customer">Customer</label>
      <select class="form-select" id="reservation-customer" name="customer_id" required <?= \Crms\Auth::isCustomer() || $editing ? 'disabled' : '' ?>>
        <?php foreach ($customers as $c): ?>
          <option value="<?= (int)$c['customer_id'] ?>" <?= (int) ($preCustomerId ?? 0) === (int) $c['customer_id'] ? 'selected' : '' ?>><?= e($c['customer_code'] . ' · ' . $c['first_name'] . ' ' . $c['last_name']) ?></option>
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
    <?php if (!\Crms\Auth::isCustomer() && !$rentalOfficer): ?>
      <div class="col-md-4"><label class="form-label" for="reservation-discount">Discount (RWF)</label><input class="form-control" id="reservation-discount" type="number" min="0" step="100" name="discount_amount" value="<?= e($reservation['discount_amount'] ?? '0') ?>"></div>
      <div class="col-12"><label class="form-label" for="reservation-internal-notes">Internal notes</label><textarea class="form-control" id="reservation-internal-notes" name="internal_notes"><?= e($reservation['internal_notes'] ?? '') ?></textarea></div>
    <?php elseif ($rentalOfficer): ?>
      <div class="col-12"><p class="small text-muted mb-0">Reservation pricing uses the approved fleet rates. Discounts require authorized approval.</p></div>
      <div class="col-12"><label class="form-label" for="reservation-internal-notes">Internal notes</label><textarea class="form-control" id="reservation-internal-notes" name="internal_notes"><?= e($reservation['internal_notes'] ?? '') ?></textarea></div>
    <?php elseif (!\Crms\Auth::isCustomer()): ?>
      <div class="col-12"><label class="form-label" for="reservation-internal-notes">Internal notes</label><textarea class="form-control" id="reservation-internal-notes" name="internal_notes"><?= e($reservation['internal_notes'] ?? '') ?></textarea></div>
    <?php endif; ?>
    <div class="col-12"><label class="form-label" for="reservation-customer-notes">Customer notes</label><textarea class="form-control" id="reservation-customer-notes" name="customer_notes"><?= e($reservation['customer_notes'] ?? '') ?></textarea></div>
  </div>
  <section class="border rounded-3 p-3 mt-4" aria-labelledby="reservation-review-title">
    <h2 class="h6" id="reservation-review-title"><?= $customerSigning ? 'Review and sign the reservation request' : 'Review the reservation request' ?></h2>
    <p class="small text-muted">Review the vehicle, dates, locations, and customer notes above. The final quote and availability are checked again by the server. Staff changes require the customer to sign again before approval.</p>
    <label class="form-check mb-3">
      <input class="form-check-input" type="checkbox" name="reservation_reviewed" value="1" required>
      <span class="form-check-label"><?= $customerSigning ? 'I have reviewed these reservation details and agree to submit this signed request.' : 'I have reviewed these reservation details and agree to save this request.' ?></span>
    </label>
    <?php if ($customerSigning): ?>
    <label class="form-label" for="customer-signature-canvas">Customer signature</label>
    <canvas id="customer-signature-canvas" width="600" height="180" class="w-100 border rounded bg-white" style="height:180px;touch-action:none" role="img" aria-label="Draw customer signature here" tabindex="0"></canvas>
    <input type="hidden" name="customer_signature" id="customer-signature-data">
    <div class="d-flex justify-content-between align-items-center mt-2">
      <small class="text-muted">Use a mouse, touch screen, or stylus to sign.</small>
      <button class="btn btn-sm btn-outline-secondary" type="button" id="clear-customer-signature">Clear signature</button>
    </div>
    <div class="small text-danger mt-2" id="customer-signature-error" role="alert" hidden>Draw your signature before submitting.</div>
    <?php else: ?>
      <p class="small text-muted mb-0">The customer must sign this request from their account before a Rental Officer can approve it.</p>
    <?php endif; ?>
  </section>
  <p class="small text-muted mt-3"><?= $customerSigning ? 'After submission, the request remains pending until a Rental Officer records a signed decision. Payment is available only after approval.' : 'The request remains pending until the customer signs and a Rental Officer records a signed decision.' ?></p>
  <div class="d-flex gap-2 mt-2"><button class="btn btn-primary"><?= $customerSigning ? ($editing ? 'Sign and update request' : 'Sign and submit request') : ($editing ? 'Save request' : 'Save reservation request') ?></button><?php if ($editing): ?><a class="btn btn-outline-secondary" href="<?= e(url('reservations/show', ['id' => $reservation['reservation_id']])) ?>">Cancel</a><?php endif; ?></div>
</form>
<?php if ($customerSigning): ?>
<script>
(() => {
  const canvas = document.getElementById('customer-signature-canvas');
  const form = canvas?.closest('form');
  const hidden = document.getElementById('customer-signature-data');
  const error = document.getElementById('customer-signature-error');
  const context = canvas?.getContext('2d');
  if (!canvas || !form || !hidden || !context) return;
  context.strokeStyle = '#172554';
  context.lineWidth = 3;
  context.lineCap = 'round';
  context.lineJoin = 'round';
  let drawing = false;
  let signed = false;
  const point = event => {
    const rect = canvas.getBoundingClientRect();
    return { x: (event.clientX - rect.left) * canvas.width / rect.width, y: (event.clientY - rect.top) * canvas.height / rect.height };
  };
  canvas.addEventListener('pointerdown', event => {
    event.preventDefault();
    canvas.setPointerCapture(event.pointerId);
    const p = point(event);
    context.beginPath();
    context.moveTo(p.x, p.y);
    drawing = true;
  });
  canvas.addEventListener('pointermove', event => {
    if (!drawing) return;
    const p = point(event);
    context.lineTo(p.x, p.y);
    context.stroke();
    signed = true;
  });
  const finish = () => {
    if (!drawing) return;
    drawing = false;
    if (signed) hidden.value = canvas.toDataURL('image/png');
  };
  canvas.addEventListener('pointerup', finish);
  canvas.addEventListener('pointercancel', finish);
  document.getElementById('clear-customer-signature')?.addEventListener('click', () => {
    context.clearRect(0, 0, canvas.width, canvas.height);
    hidden.value = '';
    signed = false;
    error.hidden = true;
  });
  form.addEventListener('submit', event => {
    if (signed) hidden.value = canvas.toDataURL('image/png');
    if (!hidden.value) {
      event.preventDefault();
      error.hidden = false;
      canvas.focus();
    }
  });
})();
</script>
<?php endif; ?>
