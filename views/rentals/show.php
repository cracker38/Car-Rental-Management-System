<div class="card card-body mb-3">
  <h2 class="h5"><?= e($row['rental_number']) ?> · <?= e($row['reservation_code']) ?></h2>
  <p><?= e($row['first_name'] . ' ' . $row['last_name']) ?> · <?= e($row['registration_no']) ?></p>
  <p>Rental: <?= e($row['status']) ?><?php if (can('rentals.manage')): ?> · Agreement: <?= e($row['agreement_status']) ?><?php endif; ?></p>
  <?php if (can('rentals.manage')): ?>
    <p>Base <?= e(\Crms\Money::format($row['base_amount'])) ?> · extras <?= e(\Crms\Money::format($row['extra_mileage_fee'])) ?> · late <?= e(\Crms\Money::format($row['late_return_fee'])) ?> · damage <?= e(\Crms\Money::format($row['damage_fee'])) ?> · cleaning <?= e(\Crms\Money::format($row['cleaning_fee'])) ?></p>
    <p class="mb-0"><strong>Final <?= e(\Crms\Money::format($row['final_amount'])) ?></strong></p>
  <?php endif; ?>
</div>
<?php if ($row['agreement_terms'] && can('rentals.manage')): ?>
  <pre class="bg-white p-3 border rounded small"><?= e($row['agreement_terms']) ?></pre>
<?php endif; ?>

<?php if ($row['status'] === 'scheduled' && can('rentals.manage') && !\Crms\Auth::isCustomer()): ?>
<form method="post" action="<?= e(url('rentals/pickup', ['id' => $row['rental_id']])) ?>" class="card card-body mb-3">
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
<?php endif; ?>

<?php if (in_array($row['status'], ['active','overdue'], true) && can('rentals.manage') && !\Crms\Auth::isCustomer()): ?>
<form method="post" action="<?= e(url('rentals/return', ['id' => $row['rental_id']])) ?>" class="card card-body mb-3">
  <?= \Crms\Csrf::field() ?>
  <h3 class="h6">Return</h3>
  <div class="row g-2">
    <div class="col-md-4"><label class="form-label">Return time</label><input class="form-control" type="datetime-local" name="actual_return_datetime" required></div>
    <div class="col-md-4"><label class="form-label">Mileage</label><input class="form-control" type="number" name="return_mileage" required></div>
    <div class="col-md-4"><label class="form-label">Fuel %</label><input class="form-control" name="return_fuel_percent"></div>
    <div class="col-md-4"><label class="form-label">Damage fee</label><input class="form-control" name="damage_fee" value="0"></div>
    <div class="col-md-4"><label class="form-label">Cleaning fee</label><input class="form-control" name="cleaning_fee" value="0"></div>
    <div class="col-md-4"><label class="form-label">Other</label><input class="form-control" name="other_charges" value="0"></div>
    <div class="col-12"><textarea class="form-control" name="return_notes" placeholder="Notes"></textarea></div>
  </div>
  <button class="btn btn-primary mt-2">Record return</button>
</form>
<?php endif; ?>

<?php if ($row['status'] === 'returned' && can('inspections.manage')): ?>
<form method="post" action="<?= e(url('rentals/inspect', ['id' => $row['rental_id']])) ?>" class="card card-body mb-3">
  <?= \Crms\Csrf::field() ?>
  <h3 class="h6">Return inspection</h3>
  <div class="row g-2">
    <div class="col-md-3"><input class="form-control" name="odometer_reading" placeholder="Odometer"></div>
    <div class="col-md-3"><input class="form-control" name="fuel_level_percent" placeholder="Fuel %"></div>
    <div class="col-md-3"><select class="form-select" name="exterior_condition"><option value="">Exterior</option><option>excellent</option><option>good</option><option>fair</option><option>poor</option></select></div>
    <div class="col-md-3"><select class="form-select" name="interior_condition"><option value="">Interior</option><option>excellent</option><option>good</option><option>fair</option><option>poor</option></select></div>
    <div class="col-12"><textarea class="form-control" name="damage_notes" placeholder="Damage notes"></textarea></div>
  </div>
  <button class="btn btn-primary mt-2">Save inspection</button>
</form>
<?php endif; ?>

<?php if ($row['status'] === 'inspected' && can('rentals.manage') && !\Crms\Auth::isCustomer()): ?>
<form method="post" action="<?= e(url('rentals/close', ['id' => $row['rental_id']])) ?>" data-confirm="Close this rental and generate the invoice?">
  <?= \Crms\Csrf::field() ?>
  <button class="btn btn-success">Close rental &amp; generate invoice</button>
</form>
<?php endif; ?>

<?php if ($invoice && can('invoices.view')): ?>
  <p class="mt-3">Invoice <a href="<?= e(url('invoices/show', ['id' => $invoice['invoice_id']])) ?>"><?= e($invoice['invoice_number']) ?></a></p>
<?php endif; ?>

<h3 class="h6 mt-4">Inspections</h3>
<ul>
  <?php foreach ($inspections as $i): ?>
    <li><?= e($i['inspection_type']) ?> · <?= e($i['inspected_at']) ?> · <?= e((string)$i['odometer_reading']) ?> km</li>
  <?php endforeach; ?>
</ul>
