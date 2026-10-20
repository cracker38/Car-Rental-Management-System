<section class="panel invoice-hero">
  <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
    <div>
      <p class="eyebrow mb-1">Reservation</p>
      <h2><?= e($row['reservation_code']) ?></h2>
      <p class="mb-1"><?= e($row['first_name'] . ' ' . $row['last_name']) ?></p>
      <p class="text-muted mb-0"><?= e($row['registration_no'] . ' · ' . $row['make'] . ' ' . $row['model']) ?></p>
    </div>
    <?= status_badge(\Crms\Ui::reservationStatusLabel((string) $row['status'], (string) ($row['payment_status'] ?? 'unpaid'))) ?>
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
<?php
$customerHasSigned = !empty($row['customer_signature'])
  && !empty($row['customer_signed_at'])
  && !empty($row['customer_signature_user_id'])
  && (int) $row['customer_signature_user_id'] === (int) ($row['customer_user_id'] ?? 0);
$signedApprovalComplete = $row['status'] === 'confirmed'
  && trim((string) ($row['approval_note'] ?? '')) !== ''
  && !empty($row['approver_signature'])
  && $customerHasSigned;
?>
<section class="panel mb-3">
  <div class="panel-h">Reservation request and signatures</div>
  <div class="row g-3">
    <div class="col-md-6"><strong>Pickup location</strong><div><?= e($row['pickup_location'] ?: 'Not specified') ?></div></div>
    <div class="col-md-6"><strong>Return location</strong><div><?= e($row['return_location'] ?: 'Not specified') ?></div></div>
    <div class="col-md-6"><strong>Request submitted</strong><div><?= e($row['created_at']) ?></div></div>
    <div class="col-md-6"><strong>Payment status</strong><div><?= status_badge(ucfirst(str_replace('_', ' ', (string) ($row['payment_status'] ?? 'unpaid')))) ?></div></div>
    <?php if (!empty($row['customer_notes'])): ?><div class="col-12"><strong>Customer notes</strong><div><?= nl2br(e($row['customer_notes'])) ?></div></div><?php endif; ?>
    <?php if (!\Crms\Auth::isCustomer() && !empty($row['internal_notes'])): ?><div class="col-12"><strong>Internal notes</strong><div><?= nl2br(e($row['internal_notes'])) ?></div></div><?php endif; ?>
    <div class="col-md-6">
      <strong>Customer signature</strong>
      <?php if ($customerHasSigned): ?>
        <div><img class="border rounded bg-white mt-2" style="max-width:100%;height:90px;object-fit:contain" src="<?= e(\Crms\Signature::dataUri($row['customer_signature'])) ?>" alt="Customer signature"></div>
        <small class="text-muted">Signed <?= e($row['customer_signed_at'] ?? '') ?><?php if (!empty($row['signature_captor_first_name'])): ?> · captured by <?= e($row['signature_captor_first_name'] . ' ' . $row['signature_captor_last_name']) ?><?php endif; ?></small>
      <?php else: ?><p class="small text-muted mb-0">Awaiting the customer's signature.</p><?php endif; ?>
    </div>
    <div class="col-md-6">
      <strong>Approver signature</strong>
      <?php if (!empty($row['approver_signature'])): ?>
        <div><img class="border rounded bg-white mt-2" style="max-width:100%;height:90px;object-fit:contain" src="<?= e(\Crms\Signature::dataUri($row['approver_signature'])) ?>" alt="Approver signature"></div>
        <small class="text-muted"><?= e(trim(($row['decision_first_name'] ?? '') . ' ' . ($row['decision_last_name'] ?? ''))) ?> · <?= e($row['decision_at'] ?? '') ?></small>
        <p class="small mt-2 mb-0"><strong>Decision note:</strong> <?= nl2br(e($row['approval_note'] ?? '')) ?></p>
      <?php else: ?><p class="small text-muted mb-0">No decision has been recorded.</p><?php endif; ?>
    </div>
  </div>
</section>
<?php if ($row['status'] === 'pending' && \Crms\Auth::isCustomer() && !$customerHasSigned): ?>
  <section class="panel mb-3">
    <div class="panel-h">Your signature is required</div>
    <p class="small text-muted">Review and sign this request before a Rental Officer can approve it.</p>
    <a class="btn btn-primary" href="<?= e(url('reservations/form', ['id' => $row['reservation_id']])) ?>">Review and sign request</a>
  </section>
<?php endif; ?>
<?php if ($row['status'] === 'pending' && can('reservations.decide') && !\Crms\Auth::isCustomer() && !$customerHasSigned): ?>
  <div class="alert alert-info">Awaiting the customer's signature. This reservation cannot be approved yet.</div>
<?php endif; ?>
<?php if ($row['status'] === 'pending' && can('reservations.decide') && !\Crms\Auth::isCustomer() && $customerHasSigned): ?>
  <form method="post" action="<?= e(url('reservations/decision', ['id' => $row['reservation_id']])) ?>" class="panel mb-3" id="reservation-decision-form">
    <?= \Crms\Csrf::field() ?>
    <div class="panel-h">Review reservation request</div>
    <p class="small text-muted">Review the vehicle and dates before deciding. A decision note and your Rental Officer signature are required. Approval creates the booking invoice and unlocks customer payment.</p>
    <label class="form-label" for="reservation-decision-note">Decision note</label>
    <textarea class="form-control mb-3" id="reservation-decision-note" name="note" maxlength="500" rows="3" required></textarea>
    <label class="form-label" for="approver-signature-canvas">Rental Officer signature</label>
    <canvas id="approver-signature-canvas" width="600" height="180" class="w-100 border rounded bg-white" style="height:180px;touch-action:none" role="img" aria-label="Draw Rental Officer signature here" tabindex="0"></canvas>
    <input type="hidden" name="approver_signature" id="approver-signature-data">
    <div class="d-flex justify-content-between align-items-center mt-2">
      <small class="text-muted">Sign using a mouse, touch screen, or stylus.</small>
      <button class="btn btn-sm btn-outline-secondary" type="button" id="clear-approver-signature">Clear signature</button>
    </div>
    <div class="small text-danger mt-2" id="approver-signature-error" role="alert" hidden>Draw your signature before recording the decision.</div>
    <div class="d-flex gap-2 mt-3">
      <button class="btn btn-primary" name="decision" value="approve">Approve reservation</button>
      <button class="btn btn-outline-danger" name="decision" value="reject">Reject reservation</button>
    </div>
  </form>
  <script>
  (() => {
    const canvas = document.getElementById('approver-signature-canvas');
    const form = document.getElementById('reservation-decision-form');
    const hidden = document.getElementById('approver-signature-data');
    const error = document.getElementById('approver-signature-error');
    const context = canvas?.getContext('2d');
    if (!canvas || !form || !hidden || !context) return;
    context.strokeStyle = '#172554'; context.lineWidth = 3; context.lineCap = 'round'; context.lineJoin = 'round';
    let drawing = false; let signed = false;
    const point = event => { const rect = canvas.getBoundingClientRect(); return { x: (event.clientX - rect.left) * canvas.width / rect.width, y: (event.clientY - rect.top) * canvas.height / rect.height }; };
    canvas.addEventListener('pointerdown', event => { event.preventDefault(); canvas.setPointerCapture(event.pointerId); const p = point(event); context.beginPath(); context.moveTo(p.x, p.y); drawing = true; });
    canvas.addEventListener('pointermove', event => { if (!drawing) return; const p = point(event); context.lineTo(p.x, p.y); context.stroke(); signed = true; });
    const finish = () => { if (!drawing) return; drawing = false; if (signed) hidden.value = canvas.toDataURL('image/png'); };
    canvas.addEventListener('pointerup', finish); canvas.addEventListener('pointercancel', finish);
    document.getElementById('clear-approver-signature')?.addEventListener('click', () => { context.clearRect(0, 0, canvas.width, canvas.height); hidden.value = ''; signed = false; error.hidden = true; });
    form.addEventListener('submit', event => { if (signed) hidden.value = canvas.toDataURL('image/png'); if (!hidden.value) { event.preventDefault(); error.hidden = false; } });
  })();
  </script>
<?php endif; ?>
<?php if (!empty($bookingInvoice) && $bookingInvoice['status'] !== 'void'): ?>
  <section class="panel mb-3">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
      <div>
        <div class="panel-h">Rental prepayment</div>
        <p class="mb-1">Invoice <?php if (!\Crms\Auth::isCustomer() || $bookingInvoice['status'] !== 'draft'): ?><a href="<?= e(url('invoices/show', ['id' => $bookingInvoice['invoice_id']])) ?>"><?= e($bookingInvoice['invoice_number']) ?></a><?php else: ?><?= e($bookingInvoice['invoice_number']) ?><?php endif; ?></p>
        <p class="small text-muted mb-0">Estimated hire: <?= e(\Crms\Money::format($bookingInvoice['total_amount'])) ?> · Paid: <?= e(\Crms\Money::format($bookingInvoice['net_paid'] ?? 0)) ?> · Balance: <?= e(\Crms\Money::format($bookingInvoice['outstanding_balance'] ?? $bookingInvoice['total_amount'])) ?></p>
      </div>
      <?= status_badge((string) $bookingInvoice['status']) ?>
    </div>
    <?php if (\Crms\Auth::isCustomer() && (float) ($bookingInvoice['outstanding_balance'] ?? $bookingInvoice['total_amount']) > 0 && !in_array($bookingInvoice['status'], ['draft', 'void'], true)): ?>
      <?php if ($signedApprovalComplete): ?>
        <p class="small text-muted mt-2 mb-2">Pay the confirmed hire quote before the rental agreement is issued and before vehicle pickup. Approved extra charges, if any, are invoiced after return inspection.</p>
        <a class="btn btn-primary" href="<?= e(url('invoices/show', ['id' => $bookingInvoice['invoice_id']])) ?>">Pay booking invoice</a>
      <?php else: ?>
        <p class="small text-muted mt-2 mb-0">Payment is available after a Rental Officer records a signed decision on your signed request.</p>
      <?php endif; ?>
    <?php elseif (\Crms\Auth::isCustomer() && $bookingInvoice['status'] === 'draft'): ?>
      <p class="small text-muted mt-2 mb-0">Your booking invoice will be issued after the reservation is confirmed.</p>
    <?php elseif ((float) ($bookingInvoice['outstanding_balance'] ?? 0) <= 0): ?>
      <p class="small text-success mt-2 mb-0"><?php if ((float) $bookingInvoice['total_amount'] <= 0): ?>No hire prepayment is due for this quote.<?php else: ?>Booking prepayment received.<?php endif; ?> The rental agreement can proceed once any required security deposit is settled.</p>
    <?php endif; ?>
    <?php if (\Crms\Auth::isCustomer() && in_array($row['status'], ['pending', 'confirmed'], true)): ?>
      <p class="small text-muted mt-2 mb-0">If you cancel a prepaid reservation, any refund follows the business approval workflow and is not issued automatically.</p>
    <?php elseif (\Crms\Auth::isCustomer() && in_array($row['status'], ['cancelled', 'rejected', 'no_show'], true) && ((float) ($bookingInvoice['net_paid'] ?? 0) > 0 || (float) ($depositSummary['held'] ?? 0) > 0)): ?>
      <p class="small text-warning-emphasis mt-2 mb-0">This reservation has prepaid funds. An accountant must review any refund or cancellation fee under the approval workflow.</p>
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
  <?php if (\Crms\Auth::isCustomer() && $signedApprovalComplete && $depositSummary['requestable'] > 0): ?>
    <form method="post" action="<?= e(url('payments/momo')) ?>" class="mt-3">
      <?= \Crms\Csrf::field() ?>
      <input type="hidden" name="reservation_id" value="<?= (int) $row['reservation_id'] ?>">
      <input type="hidden" name="transaction_type" value="deposit_received">
      <input type="hidden" name="amount" value="<?= e((string) (int) round($depositSummary['requestable'])) ?>">
      <p class="small text-muted">Pay the remaining security deposit with MTN or Airtel Mobile Money. PawaPay must confirm it before a rental agreement can be issued or the vehicle picked up. The quoted hire charge is paid through the rental prepayment invoice above.</p>
      <div class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label" for="booking-momo-phone">MoMo phone</label><input class="form-control" id="booking-momo-phone" name="phone" required value="<?= e((string) $row['phone']) ?>" placeholder="07xxxxxxxx" inputmode="tel"></div>
        <div class="col-md-3"><label class="form-label" for="booking-momo-provider">Mobile network</label><select class="form-select" id="booking-momo-provider" name="provider" required><option value="">Choose network</option><option value="MTN_MOMO_RWA">MTN</option><option value="AIRTEL_RWA">Airtel</option></select></div>
        <div class="col-md-3"><label class="form-label">Amount due</label><input class="form-control" value="<?= e(\Crms\Money::format($depositSummary['requestable'])) ?>" readonly></div>
        <div class="col-md-2 d-grid"><button class="btn btn-primary">Pay booking deposit</button></div>
      </div>
    </form>
  <?php elseif ($depositSummary['requestable'] > 0 && !$signedApprovalComplete): ?>
    <p class="small text-muted mt-2 mb-0">The required deposit becomes payable after the signed reservation request is approved.</p>
  <?php endif; ?>
  <?php if (!empty($depositTransactions)): ?>
    <div class="table-responsive mt-3"><table class="table table-sm mb-0"><thead><tr><th>Deposit request</th><th>Amount</th><th>Status</th><th></th></tr></thead><tbody>
      <?php foreach ($depositTransactions as $depositTransaction): ?>
        <tr <?= $depositTransaction['status'] === 'pending' && \Crms\Auth::isCustomer() ? 'data-payment-poll="' . e(url('payments/status', ['id' => $depositTransaction['transaction_id']])) . '"' : '' ?>><td><?= e($depositTransaction['transaction_number']) ?></td><td><?= e(\Crms\Money::format($depositTransaction['amount'])) ?></td><td><?= e(ucfirst($depositTransaction['status'])) ?></td><td><?php if ($depositTransaction['status'] === 'pending' && \Crms\Auth::isCustomer()): ?><form method="post" action="<?= e(url('payments/sync', ['id' => $depositTransaction['transaction_id']])) ?>"><?= \Crms\Csrf::field() ?><button class="btn btn-sm btn-outline-secondary">Check status</button></form><?php elseif ($depositTransaction['status'] === 'completed'): ?><a class="btn btn-sm btn-outline-dark" href="<?= e(url('payments/receipt', ['id' => $depositTransaction['transaction_id']])) ?>">Receipt</a><?php endif; ?></td></tr>
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
  <?php if (!empty($protectedDriverAssignment)): ?>
    <section class="panel mb-3"><div class="panel-h">Chauffeur assignment</div><p class="text-muted mb-0">This driver assignment is managed by the System Administrator.</p></section>
  <?php else: ?>
  <form method="post" action="<?= e(url('drivers/assign')) ?>" class="panel mb-3">
    <?= \Crms\Csrf::field() ?><input type="hidden" name="reservation_id" value="<?= (int)$row['reservation_id'] ?>">
    <div class="panel-h">Chauffeur assignment</div>
    <div class="row g-2 align-items-end"><div class="col-md-5"><label class="form-label" for="reservation-driver">Driver</label><select class="form-select" id="reservation-driver" name="driver_id"><option value="">No driver assigned</option><?php foreach (($drivers ?? []) as $driver): ?><option value="<?= (int)$driver['driver_id'] ?>" <?= (int)($assignment['driver_id'] ?? 0) === (int)$driver['driver_id'] ? 'selected' : '' ?>><?= e($driver['first_name'] . ' ' . $driver['last_name']) ?> · <?= e($driver['phone']) ?></option><?php endforeach; ?></select></div><div class="col-md-5"><label class="form-label" for="driver-assignment-notes">Notes</label><input class="form-control" id="driver-assignment-notes" name="notes" value="<?= e($assignment['notes'] ?? '') ?>" placeholder="Pickup instructions"></div><div class="col-md-2 d-grid"><button class="btn btn-outline-primary">Save</button></div></div>
    <small class="text-muted mt-2">A driver cannot be assigned to another overlapping reservation.</small>
  </form>
  <?php endif; ?>
<?php endif; ?>
<?php if ($row['status'] === 'confirmed' && !$rental && can('rentals.manage') && !\Crms\Auth::isCustomer()): ?>
  <form method="post" action="<?= e(url('rentals/create')) ?>" class="panel">
    <?= \Crms\Csrf::field() ?>
    <input type="hidden" name="reservation_id" value="<?= (int)$row['reservation_id'] ?>">
    <div class="panel-h">Rental agreement</div>
    <label class="form-label">Agreement terms</label>
    <textarea class="form-control mb-2" name="agreement_terms" rows="6" placeholder="Leave blank to use the standard Rwanda hire terms."></textarea>
    <?php if ((!$bookingInvoice || $bookingInvoice['status'] === 'paid') && (float) ($depositSummary['due'] ?? 0) <= 0): ?>
      <button class="btn btn-primary">Create rental agreement</button>
    <?php else: ?>
      <button class="btn btn-primary" disabled>Payment required before agreement</button>
      <p class="small text-muted mt-2 mb-0">Wait for the booking invoice and any configured security deposit to be confirmed.</p>
    <?php endif; ?>
  </form>
<?php elseif ($rental): ?>
  <p class="mt-3">Rental: <a href="<?= e(url('rentals/show', ['id' => $rental['rental_id']])) ?>"><?= e($rental['rental_number']) ?></a></p>
<?php endif; ?>
