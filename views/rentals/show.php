<section class="panel invoice-hero mb-3">
  <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
    <div>
      <p class="eyebrow mb-1">Rental</p>
      <h2><?= e($row['rental_number']) ?></h2>
      <p class="mb-0 text-muted"><?= e($row['reservation_code']) ?> · <?= e($row['first_name'] . ' ' . $row['last_name']) ?> · <?= e($row['registration_no']) ?></p>
    </div>
    <?= status_badge((string)($row['display_status'] ?? $row['status'])) ?>
  </div>
  <?php if (can('rentals.manage') || can('rentals.view')): ?>
    <div class="kpi-row">
      <div><span>Base</span><strong><?= e(\Crms\Money::format($row['base_amount'])) ?></strong></div>
      <div><span>Extras</span><strong><?= e(\Crms\Money::format($row['extra_mileage_fee'])) ?></strong></div>
      <div><span>Late</span><strong><?= e(\Crms\Money::format($row['late_return_fee'])) ?></strong></div>
      <div><span>Final</span><strong><?= e(\Crms\Money::format($row['final_amount'])) ?></strong></div>
    </div>
    <p class="small text-muted mb-0">Agreement: <?= e($row['agreement_status']) ?> · damage <?= e(\Crms\Money::format($row['damage_fee'])) ?> · cleaning <?= e(\Crms\Money::format($row['cleaning_fee'])) ?></p>
  <?php endif; ?>
</section>
<?php if ($row['agreement_terms'] && (can('rentals.manage') || can('rentals.view'))): ?>
  <?php $agreementTotal = \Crms\Money::add(\Crms\Money::sub($row['estimated_amount'], $row['discount_amount']), $row['tax_amount']); ?>
  <div class="d-flex justify-content-end mb-2 agreement-actions"><button class="btn btn-sm btn-outline-dark" type="button" onclick="window.print()"><i class="bi bi-printer me-1" aria-hidden="true"></i>Print agreement</button></div>
  <article class="panel rental-agreement-document">
    <header class="rental-agreement-header">
      <div>
        <p class="eyebrow mb-1">Vehicle rental agreement</p>
        <h1><?= e($businessName ?? 'Rent Car Rwanda') ?></h1>
        <p class="text-muted mb-0">Rental agreement and handover terms</p>
      </div>
      <dl class="rental-agreement-reference mb-0">
        <div><dt>Agreement</dt><dd><?= e($row['rental_number']) ?></dd></div>
        <div><dt>Reservation</dt><dd><?= e($row['reservation_code']) ?></dd></div>
        <div><dt>Issued</dt><dd><?= e($row['created_at']) ?></dd></div>
        <div><dt>Status</dt><dd><?= e(ucfirst($row['agreement_status'])) ?></dd></div>
      </dl>
    </header>

    <div class="rental-agreement-parties">
      <section>
        <h2>Customer</h2>
        <strong><?= e(trim($row['first_name'] . ' ' . $row['last_name'])) ?></strong>
        <?php if (!empty($row['phone'])): ?><div><?= e($row['phone']) ?></div><?php endif; ?>
        <?php if (!empty($row['email'])): ?><div><?= e($row['email']) ?></div><?php endif; ?>
        <?php if (!empty($row['address'])): ?><div><?= e($row['address']) ?></div><?php endif; ?>
        <?php if (!empty($row['driving_license_number'])): ?><div>Driving licence: <?= e($row['driving_license_number']) ?></div><?php endif; ?>
      </section>
      <section>
        <h2>Rental vehicle</h2>
        <strong><?= e(trim($row['make'] . ' ' . $row['model'])) ?></strong>
        <div>Registration: <?= e($row['registration_no']) ?></div>
        <?php if (!empty($row['vin'])): ?><div>VIN: <?= e($row['vin']) ?></div><?php endif; ?>
        <div><?= e(trim(($row['manufacture_year'] ?? '') . ' ' . ($row['colour'] ?? '') . ' ' . ($row['transmission'] ?? '') . ' ' . ($row['fuel_type'] ?? ''))) ?></div>
      </section>
    </div>

    <section class="rental-agreement-section">
      <h2>Hire period and handover locations</h2>
      <div class="table-responsive"><table class="table rental-agreement-table mb-0">
        <tbody>
          <tr><th scope="row">Scheduled pickup</th><td><?= e($row['booking_pickup_datetime']) ?></td><th scope="row">Pickup location</th><td><?= e($row['pickup_location'] ?: 'To be confirmed') ?></td></tr>
          <tr><th scope="row">Scheduled return</th><td><?= e($row['booking_return_datetime']) ?></td><th scope="row">Return location</th><td><?= e($row['return_location'] ?: 'To be confirmed') ?></td></tr>
        </tbody>
      </table></div>
    </section>

    <section class="rental-agreement-section">
      <h2>Agreed charges</h2>
      <div class="table-responsive"><table class="table rental-agreement-table mb-0">
        <tbody>
          <tr><th scope="row">Hire length</th><td><?= (int) $row['estimated_days'] ?> billable day(s)</td><th scope="row">Agreed daily rate</th><td><?= e(\Crms\Money::format($row['agreed_daily_rate'])) ?></td></tr>
          <tr><th scope="row">Rental subtotal</th><td><?= e(\Crms\Money::format($row['base_amount'])) ?></td><th scope="row">Discount</th><td><?= e(\Crms\Money::format($row['discount_amount'])) ?></td></tr>
          <tr><th scope="row">Tax</th><td><?= e(\Crms\Money::format($row['tax_amount'])) ?></td><th scope="row">Security deposit</th><td><?= e(\Crms\Money::format($row['security_deposit'])) ?></td></tr>
          <tr class="rental-agreement-total"><th scope="row">Agreed rental charge</th><td colspan="3"><?= e(\Crms\Money::format($agreementTotal)) ?> <small>Security deposit is held separately and is not included in this charge.</small></td></tr>
        </tbody>
      </table></div>
    </section>

    <section class="rental-agreement-section rental-agreement-terms">
      <h2>Terms and conditions</h2>
      <div><?= nl2br(e($row['agreement_terms'])) ?></div>
    </section>

    <section class="rental-agreement-signatures">
      <div><strong>Customer acceptance</strong><span>Customer signature</span><span>Name</span><span>Date and time</span></div>
      <div><strong>Rental officer</strong><span>Officer signature</span><span>Name</span><span>Date and time</span></div>
    </section>
    <footer class="rental-agreement-footer">Keep a copy of this agreement. Any extra mileage, late return, damage, cleaning, or other charges are assessed and invoiced under the approved business workflow.</footer>
  </article>
<?php endif; ?>

<?php if (!empty($row['actual_pickup_datetime']) || !empty($row['actual_return_datetime'])): ?>
  <section class="panel mb-3">
    <div class="panel-h">Handover record</div>
    <div class="row g-3">
      <?php if (!empty($row['actual_pickup_datetime'])): ?>
        <div class="col-md-6"><strong>Pickup</strong><div><?= e($row['actual_pickup_datetime']) ?> · <?= e((string) $row['pickup_mileage']) ?> km<?php if ($row['pickup_fuel_percent'] !== null): ?> · <?= e((string) $row['pickup_fuel_percent']) ?>% fuel<?php endif; ?></div><?php if (!empty($row['pickup_notes'])): ?><p class="small text-muted mb-0 mt-1"><?= nl2br(e($row['pickup_notes'])) ?></p><?php endif; ?></div>
      <?php endif; ?>
      <?php if (!empty($row['actual_return_datetime'])): ?>
        <div class="col-md-6"><strong>Return</strong><div><?= e($row['actual_return_datetime']) ?> · <?= e((string) $row['return_mileage']) ?> km<?php if ($row['return_fuel_percent'] !== null): ?> · <?= e((string) $row['return_fuel_percent']) ?>% fuel<?php endif; ?></div><?php if (!empty($row['return_notes'])): ?><p class="small text-muted mb-0 mt-1"><?= nl2br(e($row['return_notes'])) ?></p><?php endif; ?></div>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>

<?php $lateReturn = !empty($row['actual_return_datetime']) && $row['actual_return_datetime'] > $row['expected_return_datetime']; ?>
<?php if ($lateReturn || (float) $row['extra_mileage_fee'] > 0 || (float) $row['late_return_fee'] > 0 || (float) $row['damage_fee'] > 0 || (float) $row['cleaning_fee'] > 0 || (float) $row['other_charges'] > 0): ?>
  <section class="panel mb-3">
    <div class="panel-h">Charges flagged for accountant review</div>
    <?php if ($lateReturn): ?><p class="small text-warning-emphasis mb-2"><strong>Late return:</strong> actual return was after the expected return time.</p><?php endif; ?>
    <div class="kpi-row">
      <div><span>Extra mileage</span><strong><?= e(\Crms\Money::format($row['extra_mileage_fee'])) ?></strong></div>
      <div><span>Late return</span><strong><?= e(\Crms\Money::format($row['late_return_fee'])) ?></strong></div>
      <div><span>Damage</span><strong><?= e(\Crms\Money::format($row['damage_fee'])) ?></strong></div>
      <div><span>Cleaning</span><strong><?= e(\Crms\Money::format($row['cleaning_fee'])) ?></strong></div>
      <div><span>Other</span><strong><?= e(\Crms\Money::format($row['other_charges'])) ?></strong></div>
    </div>
    <p class="small text-muted mb-0">These are rental charge records. They do not post a payment or financial adjustment.</p>
  </section>
<?php endif; ?>

<?php if ($row['status'] === 'scheduled' && can('rentals.manage') && !\Crms\Auth::isCustomer()): ?>
<form method="post" action="<?= e(url('rentals/pickup', ['id' => $row['rental_id']])) ?>" class="panel mb-3">
  <?= \Crms\Csrf::field() ?>
  <h3 class="h6">Pickup</h3>
  <div class="row g-2">
    <div class="col-md-4"><label class="form-label">Pickup time</label><input class="form-control" type="datetime-local" name="actual_pickup_datetime" required></div>
    <div class="col-md-4"><label class="form-label">Mileage</label><input class="form-control" type="number" name="pickup_mileage" required></div>
    <div class="col-md-4"><label class="form-label">Fuel %</label><input class="form-control" name="pickup_fuel_percent"></div>
    <div class="col-md-4"><label class="form-label">Exterior</label><select class="form-select" name="exterior_condition"><option value="">—</option><option>excellent</option><option>good</option><option>fair</option><option>poor</option></select></div>
    <div class="col-md-4"><label class="form-label">Interior</label><select class="form-select" name="interior_condition"><option value="">—</option><option>excellent</option><option>good</option><option>fair</option><option>poor</option></select></div>
    <div class="col-md-4"><label class="form-label">Cleanliness</label><select class="form-select" name="cleanliness"><option value="">—</option><option>clean</option><option>acceptable</option><option>needs_cleaning</option></select></div>
    <div class="col-12"><textarea class="form-control" name="pickup_notes" placeholder="Notes"></textarea></div>
  </div>
  <button class="btn btn-primary mt-2">Record pickup</button>
</form>
<form method="post" action="<?= e(url('rentals/cancel', ['id' => $row['rental_id']])) ?>" class="panel mb-3" data-confirm="Cancel this agreement and release the vehicle booking?">
  <?= \Crms\Csrf::field() ?>
  <h3 class="h6">Cancel before pickup</h3>
  <div class="row g-2 align-items-end">
    <div class="col-md-9"><label class="form-label" for="rental-cancel-reason">Reason</label><input class="form-control" id="rental-cancel-reason" name="reason" maxlength="500" required></div>
    <div class="col-md-3 d-grid"><button class="btn btn-outline-danger">Cancel agreement</button></div>
  </div>
  <div class="form-text">Only scheduled rentals can be cancelled here. Once pickup is recorded, complete a return instead.</div>
</form>
<?php endif; ?>

<?php if (in_array($row['status'], ['active','overdue'], true) && can('rentals.manage') && !\Crms\Auth::isCustomer()): ?>
<form method="post" action="<?= e(url('rentals/return', ['id' => $row['rental_id']])) ?>" class="panel mb-3">
  <?= \Crms\Csrf::field() ?>
  <h3 class="h6">Return</h3>
  <div class="row g-2">
    <div class="col-md-4"><label class="form-label">Return time</label><input class="form-control" type="datetime-local" name="actual_return_datetime" required></div>
    <div class="col-md-4"><label class="form-label">Mileage</label><input class="form-control" type="number" name="return_mileage" required></div>
    <div class="col-md-4"><label class="form-label">Fuel %</label><input class="form-control" name="return_fuel_percent"></div>
    <div class="col-md-4"><label class="form-label">Proposed damage charge (RWF)</label><input class="form-control" type="number" min="0" step="100" name="damage_fee" value="0"></div>
    <div class="col-md-4"><label class="form-label">Proposed cleaning charge (RWF)</label><input class="form-control" type="number" min="0" step="100" name="cleaning_fee" value="0"></div>
    <div class="col-md-4"><label class="form-label">Proposed other charge (RWF)</label><input class="form-control" type="number" min="0" step="100" name="other_charges" value="0"></div>
    <div class="col-12"><label class="form-label">Handover and charge notes</label><textarea class="form-control" name="return_notes" placeholder="Describe the return condition. Required when recording proposed damage, cleaning or other charges."></textarea><div class="form-text">Extra mileage and late fees are calculated from recorded mileage, times and approved rates. Proposed charges go to the accountant for invoice review.</div></div>
  </div>
  <button class="btn btn-primary mt-2">Record return</button>
</form>
<?php endif; ?>

<?php if (in_array($row['status'], ['scheduled','active','overdue'], true) && can('inspections.manage')): ?>
<form method="post" action="<?= e(url('rentals/inspect-pickup', ['id' => $row['rental_id']])) ?>" class="panel mb-3" enctype="multipart/form-data">
  <?= \Crms\Csrf::field() ?>
  <h3 class="h6">Pickup condition inspection</h3>
  <p class="small text-muted">Record the pre-handover condition or update the pickup inspection. Saving this inspection does not change the rental status.</p>
  <div class="row g-2">
    <div class="col-md-3"><label class="form-label">Odometer (km)</label><input class="form-control" type="number" name="odometer_reading" min="0" step="1" required></div>
    <div class="col-md-3"><label class="form-label">Fuel (%)</label><input class="form-control" type="number" name="fuel_level_percent" min="0" max="100" step="0.01" required></div>
    <div class="col-md-3"><label class="form-label">Exterior</label><select class="form-select" name="exterior_condition" required><option value="">Select condition</option><option>excellent</option><option>good</option><option>fair</option><option>poor</option></select></div>
    <div class="col-md-3"><label class="form-label">Interior</label><select class="form-select" name="interior_condition" required><option value="">Select condition</option><option>excellent</option><option>good</option><option>fair</option><option>poor</option></select></div>
    <div class="col-md-3"><label class="form-label">Cleanliness</label><select class="form-select" name="cleanliness"><option value="">Select condition</option><option>clean</option><option>acceptable</option><option>needs_cleaning</option></select></div>
    <div class="col-12"><label class="form-label">Existing or new damage</label><textarea class="form-control" name="damage_notes" rows="2" maxlength="10000"></textarea></div>
    <div class="col-md-6"><label class="form-label">Inspection notes</label><textarea class="form-control" name="notes" rows="2" maxlength="10000"></textarea></div>
    <div class="col-md-6"><label class="form-label">Condition photo</label><input class="form-control" type="file" name="photo" accept="image/jpeg,image/png,image/webp"></div>
  </div>
  <button class="btn btn-primary mt-2">Save pickup inspection</button>
</form>
<?php endif; ?>

<?php if ($row['status'] === 'returned' && can('inspections.manage')): ?>
<form method="post" action="<?= e(url('rentals/inspect', ['id' => $row['rental_id']])) ?>" class="panel mb-3" enctype="multipart/form-data">
  <?= \Crms\Csrf::field() ?>
  <h3 class="h6">Return inspection</h3>
  <div class="row g-2">
    <div class="col-md-3"><label class="form-label">Odometer (km)</label><input class="form-control" type="number" name="odometer_reading" min="0" step="1" required></div>
    <div class="col-md-3"><label class="form-label">Fuel (%)</label><input class="form-control" type="number" name="fuel_level_percent" min="0" max="100" step="0.01" required></div>
    <div class="col-md-3"><label class="form-label">Exterior</label><select class="form-select" name="exterior_condition" required><option value="">Select condition</option><option>excellent</option><option>good</option><option>fair</option><option>poor</option></select></div>
    <div class="col-md-3"><label class="form-label">Interior</label><select class="form-select" name="interior_condition" required><option value="">Select condition</option><option>excellent</option><option>good</option><option>fair</option><option>poor</option></select></div>
    <div class="col-md-4"><label class="form-label">Cleanliness</label><select class="form-select" name="cleanliness"><option value="">Select condition</option><option>clean</option><option>acceptable</option><option>needs_cleaning</option></select></div>
    <div class="col-12"><label class="form-label">Existing or new damage</label><textarea class="form-control" name="damage_notes" rows="2" maxlength="10000"></textarea></div>
    <div class="col-md-6"><label class="form-label">Inspection notes</label><textarea class="form-control" name="notes" rows="2" maxlength="10000"></textarea></div>
    <div class="col-md-6"><label class="form-label">Condition photo</label><input class="form-control" type="file" name="photo" accept="image/jpeg,image/png,image/webp"></div>
  </div>
  <button class="btn btn-primary mt-2">Save inspection</button>
</form>
<?php endif; ?>

<?php if ($row['status'] === 'inspected' && can('rentals.manage') && !\Crms\Auth::isCustomer()): ?>
<form method="post" action="<?= e(url('rentals/close', ['id' => $row['rental_id']])) ?>" data-confirm="Close this rental? Recorded charges will be ready for the accountant invoice workflow.">
  <?= \Crms\Csrf::field() ?>
  <button class="btn btn-success">Close rental and submit charges</button>
</form>
<?php endif; ?>

<?php if ($invoice && can('invoices.view')): ?>
  <p class="mt-3">Invoice <a href="<?= e(url('invoices/show', ['id' => $invoice['invoice_id']])) ?>"><?= e($invoice['invoice_number']) ?></a></p>
<?php endif; ?>

<h3 class="h6 mt-4">Inspections</h3>
<ul>
  <?php foreach ($inspections as $i): ?>
    <li><?= e($i['inspection_type']) ?> · <?= e($i['inspected_at']) ?> · <?= e((string)$i['odometer_reading']) ?> km<?php if (!empty($i['image_path'])): ?> · <a href="<?= e(asset($i['image_path'])) ?>" target="_blank" rel="noopener">Photo</a><?php endif; ?></li>
  <?php endforeach; ?>
</ul>
<?php foreach ($inspections as $i): ?>
  <?php if (!empty($i['damage_notes']) || !empty($i['notes'])): ?>
    <div class="small text-muted mb-2">
      <strong><?= e(ucfirst($i['inspection_type'])) ?> details:</strong>
      <?php if ($i['fuel_level_percent'] !== null): ?> Fuel: <?= e((string)$i['fuel_level_percent']) ?>%<?php endif; ?>
      <?php if (!empty($i['damage_notes'])): ?> Damage: <?= nl2br(e($i['damage_notes'])) ?><?php endif; ?>
      <?php if (!empty($i['notes'])): ?> <?= nl2br(e($i['notes'])) ?><?php endif; ?>
    </div>
  <?php endif; ?>
<?php endforeach; ?>
