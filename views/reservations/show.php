<section class="panel invoice-hero">
  <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
    <div>
      <p class="eyebrow mb-1">Reservation</p>
      <h2><?= e($row['reservation_code']) ?></h2>
      <p class="mb-1"><?= e($row['first_name'] . ' ' . $row['last_name']) ?></p>
      <p class="text-muted mb-0"><?= e($row['registration_no'] . ' · ' . $row['make'] . ' ' . $row['model']) ?></p>
    </div>
    <?= status_badge((string) $row['status']) ?>
  </div>
  <div class="kpi-row">
    <div><span>Pickup</span><strong><?= e($row['pickup_datetime']) ?></strong></div>
    <div><span>Return</span><strong><?= e($row['return_datetime']) ?></strong></div>
    <div><span>Hire</span><strong><?= e(\Crms\Money::format($row['estimated_amount'])) ?></strong></div>
    <div><span>Deposit</span><strong><?= e(\Crms\Money::format($row['security_deposit'])) ?></strong></div>
  </div>
  <?php if (($row['status'] === 'pending' && (can('reservations.manage') || \Crms\Auth::isCustomer())) || (!\Crms\Auth::isCustomer() && !empty($canEditConfirmedStaffBooking))): ?><a class="btn btn-outline-dark" href="<?= e(url('reservations/form', ['id' => $row['reservation_id']])) ?>">Edit reservation</a><?php endif; ?>
</section>
<?php
$allowed = \Crms\Services\ReservationService::TRANSITIONS[$row['status']] ?? [];
if ($rental && $row['status'] === 'confirmed') {
  $allowed = array_values(array_diff($allowed, ['cancelled', 'no_show']));
}
$mayTransition = can('reservations.manage') || (\Crms\Auth::isCustomer() && in_array('cancelled', $allowed, true));
if ($allowed && $mayTransition):
?>
  <div class="panel mb-3">
    <div class="panel-h">Next step</div>
    <div class="d-flex flex-wrap gap-2">
    <?php foreach ($allowed as $st): ?>
      <?php if (\Crms\Auth::isCustomer() && $st !== 'cancelled') continue; ?>
      <form method="post" action="<?= e(url('reservations/transition', ['id' => $row['reservation_id']])) ?>" class="d-flex gap-2 align-items-center" data-confirm="Change status to <?= e($st) ?>?">
        <?= \Crms\Csrf::field() ?>
        <input type="hidden" name="status" value="<?= e($st) ?>">
        <?php if (in_array($st, ['cancelled','rejected'], true)): ?>
          <input class="form-control form-control-sm" style="width:220px" name="reason" placeholder="Reason" maxlength="500" required>
        <?php endif; ?>
        <button class="btn btn-sm <?= $st === 'confirmed' ? 'btn-primary' : 'btn-outline-dark' ?>"><?= e(ucfirst($st)) ?></button>
      </form>
    <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>
<?php if (can('drivers.manage') && in_array($row['status'], ['pending', 'confirmed'], true)): ?>
  <form method="post" action="<?= e(url('drivers/assign')) ?>" class="panel mb-3">
    <?= \Crms\Csrf::field() ?><input type="hidden" name="reservation_id" value="<?= (int)$row['reservation_id'] ?>">
    <div class="panel-h">Chauffeur assignment</div>
    <div class="row g-2 align-items-end"><div class="col-md-5"><label class="form-label" for="reservation-driver">Driver</label><select class="form-select" id="reservation-driver" name="driver_id"><option value="">No driver assigned</option><?php foreach (($drivers ?? []) as $driver): ?><option value="<?= (int)$driver['driver_id'] ?>" <?= (int)($assignment['driver_id'] ?? 0) === (int)$driver['driver_id'] ? 'selected' : '' ?>><?= e($driver['first_name'] . ' ' . $driver['last_name']) ?> · <?= e($driver['phone']) ?></option><?php endforeach; ?></select></div><div class="col-md-5"><label class="form-label" for="driver-assignment-notes">Notes</label><input class="form-control" id="driver-assignment-notes" name="notes" value="<?= e($assignment['notes'] ?? '') ?>" placeholder="Pickup instructions"></div><div class="col-md-2 d-grid"><button class="btn btn-outline-primary">Save</button></div></div>
    <small class="text-muted mt-2">A driver cannot be assigned to another overlapping reservation.</small>
  </form>
<?php endif; ?>
<?php if ($row['status'] === 'confirmed' && !$rental && can('rentals.manage') && !\Crms\Auth::isCustomer()): ?>
  <form method="post" action="<?= e(url('rentals/create')) ?>" class="panel">
    <?= \Crms\Csrf::field() ?>
    <input type="hidden" name="reservation_id" value="<?= (int)$row['reservation_id'] ?>">
    <div class="panel-h">Rental agreement</div>
    <label class="form-label">Agreement terms</label>
    <textarea class="form-control mb-2" name="agreement_terms" rows="6">Leave blank to use the standard Rwanda hire terms.</textarea>
    <button class="btn btn-primary">Create rental agreement</button>
  </form>
<?php elseif ($rental): ?>
  <p class="mt-3">Rental: <a href="<?= e(url('rentals/show', ['id' => $rental['rental_id']])) ?>"><?= e($rental['rental_number']) ?></a></p>
<?php endif; ?>
