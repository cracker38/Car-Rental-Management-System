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
  <div class="d-flex justify-content-end mb-2"><button class="btn btn-sm btn-outline-dark" type="button" onclick="window.print()"><i class="bi bi-printer me-1" aria-hidden="true"></i>Print agreement</button></div>
  <pre class="bg-white p-3 border rounded small"><?= e($row['agreement_terms']) ?></pre>
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

<?php if ((float) $row['extra_mileage_fee'] > 0 || (float) $row['late_return_fee'] > 0 || (float) $row['damage_fee'] > 0 || (float) $row['cleaning_fee'] > 0 || (float) $row['other_charges'] > 0): ?>
  <section class="panel mb-3">
    <div class="panel-h">Charges flagged for accountant review</div>
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

<?php if ($row['status'] === 'returned' && can('inspections.manage')): ?>
<form method="post" action="<?= e(url('rentals/inspect', ['id' => $row['rental_id']])) ?>" class="panel mb-3" enctype="multipart/form-data">
  <?= \Crms\Csrf::field() ?>
  <h3 class="h6">Return inspection</h3>
  <div class="row g-2">
    <div class="col-md-3"><input class="form-control" name="odometer_reading" placeholder="Odometer"></div>
    <div class="col-md-3"><input class="form-control" name="fuel_level_percent" placeholder="Fuel %"></div>
    <div class="col-md-3"><select class="form-select" name="exterior_condition"><option value="">Exterior</option><option>excellent</option><option>good</option><option>fair</option><option>poor</option></select></div>
    <div class="col-md-3"><select class="form-select" name="interior_condition"><option value="">Interior</option><option>excellent</option><option>good</option><option>fair</option><option>poor</option></select></div>
    <div class="col-12"><textarea class="form-control" name="damage_notes" placeholder="Damage notes"></textarea></div>
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
