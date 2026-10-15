<div class="card card-body mb-3">
  <h2 class="h5"><?= e($row['reservation_code']) ?></h2>
  <p><?= e($row['first_name'] . ' ' . $row['last_name']) ?> · <?= e($row['registration_no'] . ' ' . $row['make'] . ' ' . $row['model']) ?></p>
  <p><?= e($row['pickup_datetime']) ?> → <?= e($row['return_datetime']) ?></p>
  <p>Estimated hire <?= e(\Crms\Money::format($row['estimated_amount'])) ?> · tax <?= e(\Crms\Money::format($row['tax_amount'])) ?> · deposit <?= e(\Crms\Money::format($row['security_deposit'])) ?></p>
  <p>Status: <span class="badge text-bg-primary"><?= e($row['status']) ?></span></p>
  <?php if ($row['status'] === 'pending' && (can('reservations.manage') || \Crms\Auth::isCustomer())): ?><a class="btn btn-sm btn-outline-primary" href="<?= e(url('reservations/form', ['id' => $row['reservation_id']])) ?>">Edit reservation</a><?php endif; ?>
</div>
<?php
$allowed = \Crms\Services\ReservationService::TRANSITIONS[$row['status']] ?? [];
$mayTransition = can('reservations.manage') || (\Crms\Auth::isCustomer() && in_array('cancelled', $allowed, true));
if ($allowed && $mayTransition):
?>
  <div class="card card-body mb-3">
    <h3 class="h6">Allowed status changes</h3>
    <?php foreach ($allowed as $st): ?>
      <?php if (\Crms\Auth::isCustomer() && $st !== 'cancelled') continue; ?>
      <form method="post" action="<?= e(url('reservations/transition', ['id' => $row['reservation_id']])) ?>" class="d-inline me-2" data-confirm="Change status to <?= e($st) ?>?">
        <?= \Crms\Csrf::field() ?>
        <input type="hidden" name="status" value="<?= e($st) ?>">
        <?php if (in_array($st, ['cancelled','rejected'], true)): ?>
          <input class="form-control form-control-sm d-inline-block mb-1" style="width:220px" name="reason" placeholder="Reason">
        <?php endif; ?>
        <button class="btn btn-sm btn-outline-primary"><?= e($st) ?></button>
      </form>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php if (can('drivers.manage') && in_array($row['status'], ['pending', 'confirmed'], true)): ?>
  <form method="post" action="<?= e(url('drivers/assign')) ?>" class="card card-body mb-3">
    <?= \Crms\Csrf::field() ?><input type="hidden" name="reservation_id" value="<?= (int)$row['reservation_id'] ?>">
    <h3 class="h6">Chauffeur assignment</h3>
    <div class="row g-2 align-items-end"><div class="col-md-5"><label class="form-label" for="reservation-driver">Driver</label><select class="form-select" id="reservation-driver" name="driver_id"><option value="">No driver assigned</option><?php foreach (($drivers ?? []) as $driver): ?><option value="<?= (int)$driver['driver_id'] ?>" <?= (int)($assignment['driver_id'] ?? 0) === (int)$driver['driver_id'] ? 'selected' : '' ?>><?= e($driver['first_name'] . ' ' . $driver['last_name']) ?> · <?= e($driver['phone']) ?></option><?php endforeach; ?></select></div><div class="col-md-5"><label class="form-label" for="driver-assignment-notes">Notes</label><input class="form-control" id="driver-assignment-notes" name="notes" value="<?= e($assignment['notes'] ?? '') ?>" placeholder="Pickup instructions"></div><div class="col-md-2 d-grid"><button class="btn btn-outline-primary">Save assignment</button></div></div>
    <small class="text-muted mt-2">A driver cannot be assigned to another overlapping reservation.</small>
  </form>
<?php endif; ?>
<?php if ($row['status'] === 'confirmed' && !$rental && can('rentals.manage') && !\Crms\Auth::isCustomer()): ?>
  <form method="post" action="<?= e(url('rentals/create')) ?>" class="card card-body">
    <?= \Crms\Csrf::field() ?>
    <input type="hidden" name="reservation_id" value="<?= (int)$row['reservation_id'] ?>">
    <label class="form-label">Agreement terms</label>
    <textarea class="form-control mb-2" name="agreement_terms" rows="6">Leave blank to use the standard Rwanda hire terms.</textarea>
    <button class="btn btn-primary">Create rental agreement</button>
  </form>
<?php elseif ($rental): ?>
  <p>Rental: <a href="<?= e(url('rentals/show', ['id' => $rental['rental_id']])) ?>"><?= e($rental['rental_number']) ?></a></p>
<?php endif; ?>
