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
  <?php $canEditReservation = ($row['status'] === 'pending' && (can('reservations.manage') || \Crms\Auth::isCustomer())) || (!\Crms\Auth::isCustomer() && !empty($canEditConfirmedStaffBooking)); ?>
  <?php if ($canEditReservation && empty($bookingInvoice['payment_activity'])): ?><a class="btn btn-outline-dark" href="<?= e(url('reservations/form', ['id' => $row['reservation_id']])) ?>">Edit reservation</a><?php endif; ?>
</section>
<?php if (!empty($bookingInvoice) && $bookingInvoice['status'] !== 'void'): ?>
  <section class="panel mb-3">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
      <div>
        <div class="panel-h">Rental prepayment</div>
        <p class="mb-1">Invoice <a href="<?= e(url('invoices/show', ['id' => $bookingInvoice['invoice_id']])) ?>"><?= e($bookingInvoice['invoice_number']) ?></a></p>
        <p class="small text-muted mb-0">Estimated hire: <?= e(\Crms\Money::format($bookingInvoice['total_amount'])) ?> · Paid: <?= e(\Crms\Money::format($bookingInvoice['net_paid'] ?? 0)) ?> · Balance: <?= e(\Crms\Money::format($bookingInvoice['outstanding_balance'] ?? $bookingInvoice['total_amount'])) ?></p>
      </div>
      <?= status_badge((string) $bookingInvoice['status']) ?>
    </div>
    <?php if (\Crms\Auth::isCustomer() && (float) ($bookingInvoice['outstanding_balance'] ?? $bookingInvoice['total_amount']) > 0 && !in_array($bookingInvoice['status'], ['draft', 'void'], true)): ?>
      <p class="small text-muted mt-2 mb-2">Pay the quoted hire charge before staff can confirm the booking. Approved extra charges, if any, are invoiced after return inspection.</p>
      <a class="btn btn-primary" href="<?= e(url('invoices/show', ['id' => $bookingInvoice['invoice_id']])) ?>">Pay booking invoice</a>
    <?php elseif ((float) ($bookingInvoice['outstanding_balance'] ?? 0) <= 0): ?>
      <p class="small text-success mt-2 mb-0">Booking prepayment received. Staff can now confirm the reservation.</p>
    <?php endif; ?>
    <?php if (\Crms\Auth::isCustomer() && in_array($row['status'], ['pending', 'confirmed'], true)): ?>
      <p class="small text-muted mt-2 mb-0">If you cancel a prepaid reservation, any refund follows the business approval workflow and is not issued automatically.</p>
    <?php endif; ?>
  </section>
<?php endif; ?>
<?php $depositSummary = $depositSummary ?? ['required' => 0, 'held' => 0, 'pending' => 0, 'due' => 0, 'requestable' => 0]; ?>
<section class="panel mb-3">
  <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
    <div>
      <div class="panel-h">Security deposit</div>
      <p class="mb-1">Security deposit required: <strong><?= e(\Crms\Money::format($depositSummary['required'])) ?></strong></p>
      <p class="small text-muted mb-0">Confirmed deposit: <?= e(\Crms\Money::format($depositSummary['held'])) ?><?php if ($depositSummary['pending'] > 0): ?> · MoMo pending: <?= e(\Crms\Money::format($depositSummary['pending'])) ?><?php endif; ?></p>
    </div>
    <?php if ($depositSummary['required'] <= 0): ?>
      <span class="badge text-bg-secondary">No booking deposit required</span>
    <?php elseif ($depositSummary['due'] <= 0): ?>
      <span class="badge text-bg-success">Deposit paid</span>
    <?php elseif ($depositSummary['requestable'] <= 0): ?>
      <span class="badge text-bg-warning">Waiting for payment confirmation</span>
    <?php else: ?>
      <span class="badge text-bg-warning">Deposit due <?= e(\Crms\Money::format($depositSummary['due'])) ?></span>
    <?php endif; ?>
  </div>
  <?php if (\Crms\Auth::isCustomer() && in_array($row['status'], ['pending', 'confirmed'], true) && $depositSummary['requestable'] > 0): ?>
    <form method="post" action="<?= e(url('payments/momo')) ?>" class="mt-3">
      <?= \Crms\Csrf::field() ?>
      <input type="hidden" name="reservation_id" value="<?= (int) $row['reservation_id'] ?>">
      <input type="hidden" name="transaction_type" value="deposit_received">
      <input type="hidden" name="amount" value="<?= e((string) (int) round($depositSummary['requestable'])) ?>">
      <p class="small text-muted">Pay the remaining security deposit with MTN or Airtel Mobile Money. The reservation stays pending until HDEV confirms payment and a staff member confirms the booking. The quoted hire charge is paid through the rental prepayment invoice above.</p>
      <div class="row g-2 align-items-end">
        <div class="col-md-5"><label class="form-label" for="booking-momo-phone">MoMo phone</label><input class="form-control" id="booking-momo-phone" name="phone" required value="<?= e((string) $row['phone']) ?>" placeholder="07xxxxxxxx" inputmode="tel"></div>
        <div class="col-md-4"><label class="form-label">Amount due</label><input class="form-control" value="<?= e(\Crms\Money::format($depositSummary['requestable'])) ?>" readonly></div>
        <div class="col-md-3 d-grid"><button class="btn btn-primary">Pay booking deposit</button></div>
      </div>
    </form>
  <?php elseif ($depositSummary['requestable'] > 0 && $row['status'] === 'pending'): ?>
    <p class="small text-muted mt-2 mb-0">The booking cannot be confirmed until the required deposit has been received.</p>
  <?php endif; ?>
  <?php if (!empty($depositTransactions)): ?>
    <div class="table-responsive mt-3"><table class="table table-sm mb-0"><thead><tr><th>Deposit request</th><th>Amount</th><th>Status</th><th></th></tr></thead><tbody>
      <?php foreach ($depositTransactions as $depositTransaction): ?>
        <tr><td><?= e($depositTransaction['transaction_number']) ?></td><td><?= e(\Crms\Money::format($depositTransaction['amount'])) ?></td><td><?= e(ucfirst($depositTransaction['status'])) ?></td><td><?php if ($depositTransaction['status'] === 'pending' && \Crms\Auth::isCustomer()): ?><form method="post" action="<?= e(url('payments/sync', ['id' => $depositTransaction['transaction_id']])) ?>"><?= \Crms\Csrf::field() ?><button class="btn btn-sm btn-outline-secondary">Check status</button></form><?php elseif ($depositTransaction['status'] === 'completed'): ?><a class="btn btn-sm btn-outline-dark" href="<?= e(url('payments/receipt', ['id' => $depositTransaction['transaction_id']])) ?>">Receipt</a><?php endif; ?></td></tr>
      <?php endforeach; ?>
    </tbody></table></div>
  <?php endif; ?>
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
    <textarea class="form-control mb-2" name="agreement_terms" rows="6" placeholder="Leave blank to use the standard Rwanda hire terms."></textarea>
    <button class="btn btn-primary">Create rental agreement</button>
  </form>
<?php elseif ($rental): ?>
  <p class="mt-3">Rental: <a href="<?= e(url('rentals/show', ['id' => $rental['rental_id']])) ?>"><?= e($rental['rental_number']) ?></a></p>
<?php endif; ?>
