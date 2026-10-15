<div class="card card-body mb-3">
  <h2 class="h5"><?= e($row['reservation_code']) ?></h2>
  <p><?= e($row['first_name'] . ' ' . $row['last_name']) ?> · <?= e($row['registration_no'] . ' ' . $row['make'] . ' ' . $row['model']) ?></p>
  <p><?= e($row['pickup_datetime']) ?> → <?= e($row['return_datetime']) ?></p>
  <p>Estimated hire <?= e(\Crms\Money::format($row['estimated_amount'])) ?> · tax <?= e(\Crms\Money::format($row['tax_amount'])) ?> · deposit <?= e(\Crms\Money::format($row['security_deposit'])) ?></p>
  <p>Status: <span class="badge text-bg-primary"><?= e($row['status']) ?></span></p>
</div>
<?php
$allowed = \Crms\Services\ReservationService::TRANSITIONS[$row['status']] ?? [];
if ($allowed && can('reservations.manage')):
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
