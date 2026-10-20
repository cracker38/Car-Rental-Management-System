<?php
$dashboardSubtitle = 'My Account - manage your bookings, rentals, payments, and contact details.';
$dashboardNotificationHeading = 'Booking, payment, and return updates';
$kpiColClass = 'col-6 col-lg';
$dashboardActions = [
  ['route' => 'reports', 'label' => 'My reservation report', 'icon' => 'bi-graph-up'],
  ['route' => 'search', 'label' => 'Search vehicles', 'icon' => 'bi-search'],
  ['route' => 'reservations/form', 'label' => 'Request a reservation', 'icon' => 'bi-plus-circle'],
  ['route' => 'customers/profile', 'label' => 'My profile', 'icon' => 'bi-person'],
];
require dirname(__DIR__) . '/partials/dashboard-heading.php';
require dirname(__DIR__) . '/partials/kpis.php';
require dirname(__DIR__) . '/partials/dashboard-shortcuts.php';
?>
<div class="row g-3">
  <div class="col-xl-8">
    <section class="panel table-panel h-100">
      <div class="panel-h d-flex justify-content-between">My recent reservations <a class="small fw-normal" href="<?= e(url('reservations')) ?>">All reservations</a></div>
      <?php if (!$reservations): ?><p class="text-muted mb-0">You have no reservations yet. <a href="<?= e(url('search')) ?>">Search vehicles to get started.</a></p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th>Booking</th><th>Vehicle</th><th>Dates</th><th>Status</th><th></th></tr></thead>
          <tbody><?php foreach ($reservations as $item): ?><tr>
            <td><a href="<?= e(url('reservations/show', ['id' => $item['reservation_id']])) ?>"><?= e($item['reservation_code']) ?></a></td>
            <td><div class="dashboard-vehicle-cell"><img class="dashboard-vehicle-thumb" src="<?= e(vehicle_img($item['image_path'] ?? null, $item['category_name'] ?? null)) ?>" alt="<?= e($item['make'] . ' ' . $item['model']) ?>"><span><strong><?= e($item['make'] . ' ' . $item['model']) ?></strong><small class="d-block text-muted"><?= e($item['registration_no']) ?></small></span></div></td><td><?= e($item['pickup_datetime']) ?><small class="d-block text-muted"><?= e($item['return_datetime']) ?></small></td>
            <td><?= status_badge(\Crms\Ui::reservationStatusLabel((string) $item['status'], (string) ($item['payment_status'] ?? 'unpaid'))) ?><?php $signatureMissing = $item['status'] === 'pending' && (empty($item['customer_signed_at']) || (int) ($item['customer_signature_user_id'] ?? 0) !== (int) \Crms\Auth::id()); ?><?php if ($signatureMissing): ?><small class="d-block text-warning-emphasis">Your signature is needed</small><?php endif; ?></td>
            <td><?php if ($signatureMissing): ?><a class="btn btn-sm btn-outline-primary" href="<?= e(url('reservations/show', ['id' => $item['reservation_id']])) ?>">Review &amp; sign</a><?php endif; ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-xl-4">
    <section class="panel h-100">
      <div class="panel-h d-flex justify-content-between">My invoices <a class="small fw-normal" href="<?= e(url('invoices')) ?>">All invoices</a></div>
      <?php if (!$invoices): ?><p class="text-muted mb-0">Your invoices will appear here when they are issued.</p><?php else: ?>
        <?php foreach ($invoices as $invoice): ?>
          <?php
            $invoiceBalance = (float) ($invoice['outstanding_balance'] ?? 0);
            $invoicePending = (float) ($invoice['pending_momo_amount'] ?? 0);
            $paymentRequestable = max(0, $invoiceBalance - $invoicePending);
            $bookingPaymentBlocked = strncmp((string) ($invoice['notes'] ?? ''), 'Booking prepayment:', 19) === 0
              && (($invoice['reservation_status'] ?? '') !== 'confirmed' || trim((string) ($invoice['approval_note'] ?? '')) === ''
                || empty($invoice['approver_signature']) || empty($invoice['customer_signature']) || empty($invoice['customer_signed_at'])
                || empty($invoice['customer_signature_user_id']) || (int) $invoice['customer_signature_user_id'] !== (int) ($invoice['customer_user_id'] ?? 0));
          ?>
          <div class="mini-row d-flex justify-content-between gap-2">
            <span><a href="<?= e(url('invoices/show', ['id' => $invoice['invoice_id']])) ?>"><?= e($invoice['invoice_number']) ?></a><small class="d-block text-muted"><?= e($invoice['status']) ?></small></span>
            <span class="text-end">
              <?= e(\Crms\Money::format($invoiceBalance)) ?>
              <small class="d-block text-muted">Outstanding</small>
            </span>
          </div>
          <?php if ($invoicePending > 0): ?>
            <p class="small text-muted mb-2">Mobile Money request pending for <?= e(\Crms\Money::format($invoicePending)) ?>. <a href="<?= e(url('invoices/show', ['id' => $invoice['invoice_id']])) ?>">View payment status</a></p>
          <?php endif; ?>
          <?php if ($invoiceBalance > 0 && $bookingPaymentBlocked): ?>
            <p class="small text-muted mb-3">Payment is available after the signed reservation request receives an approval decision.</p>
          <?php elseif ($paymentRequestable > 0): ?>
            <form method="post" action="<?= e(url('payments/momo')) ?>" class="border rounded-3 p-3 mb-3">
              <?= \Crms\Csrf::field() ?>
              <input type="hidden" name="invoice_id" value="<?= (int) $invoice['invoice_id'] ?>">
              <input type="hidden" name="transaction_type" value="payment">
              <input type="hidden" name="return_to" value="dashboard">
              <label class="form-label small mb-1" for="invoice-phone-<?= (int) $invoice['invoice_id'] ?>">Mobile Money phone</label>
              <div class="row g-2 align-items-end">
                <div class="col-6">
                  <input id="invoice-phone-<?= (int) $invoice['invoice_id'] ?>" class="form-control form-control-sm" type="tel" name="phone" value="<?= e((string) ($invoice['phone'] ?? '')) ?>" placeholder="07xxxxxxxx" inputmode="tel" required>
                </div>
                <div class="col-6">
                  <label class="form-label small mb-1" for="invoice-amount-<?= (int) $invoice['invoice_id'] ?>">Amount (RWF)</label>
                  <input id="invoice-amount-<?= (int) $invoice['invoice_id'] ?>" class="form-control form-control-sm" type="number" name="amount" min="1" step="1" max="<?= (int) floor($paymentRequestable) ?>" value="<?= (int) floor($paymentRequestable) ?>" required>
                </div>
                <div class="col-12 d-grid">
                  <button class="btn btn-sm btn-primary" type="submit">Pay invoice with Mobile Money</button>
                </div>
              </div>
            </form>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endif; ?>
      <div class="mini-row d-flex justify-content-between mt-3"><span>Security deposit held</span><strong><?= e($deposit ?? '-') ?></strong></div>
      <?php if (!empty($upcomingReturn)): ?>
        <div class="mini-row"><strong><?= $upcomingReturn['expected_return_datetime'] < date('Y-m-d H:i:s') ? 'Return overdue' : 'Upcoming return' ?></strong><span><?= e($upcomingReturn['registration_no']) ?> &middot; <?= e($upcomingReturn['expected_return_datetime']) ?></span></div>
      <?php else: ?>
        <div class="mini-row"><strong>Upcoming return</strong><span>No scheduled returns</span></div>
      <?php endif; ?>
      <?php if (!empty($profile)): ?>
        <a class="btn btn-sm btn-outline-dark mt-3" href="<?= e(url('customers/profile')) ?>">Update my contact details</a>
      <?php endif; ?>
    </section>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-xl-6">
    <section class="panel table-panel h-100">
      <div class="panel-h d-flex justify-content-between">My rentals and agreements <a class="small fw-normal" href="<?= e(url('rentals')) ?>">All rentals</a></div>
      <?php if (!$rentals): ?><p class="text-muted mb-0">Your rental agreements will appear here after a reservation is confirmed and prepared for pickup.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th>Agreement</th><th>Vehicle</th><th>Return</th><th>Status</th></tr></thead>
          <tbody><?php foreach ($rentals as $rental): ?><tr>
            <td><a href="<?= e(url('rentals/show', ['id' => $rental['rental_id']])) ?>"><?= e($rental['rental_number']) ?></a><small class="d-block text-muted"><?= e($rental['reservation_code']) ?></small></td>
            <td><div class="dashboard-vehicle-cell"><img class="dashboard-vehicle-thumb" src="<?= e(vehicle_img($rental['image_path'] ?? null, $rental['category_name'] ?? null)) ?>" alt="<?= e($rental['make'] . ' ' . $rental['model']) ?>"><span><strong><?= e($rental['make'] . ' ' . $rental['model']) ?></strong><small class="d-block text-muted"><?= e($rental['registration_no']) ?></small></span></div></td>
            <td><?= e($rental['actual_return_datetime'] ?: $rental['expected_return_datetime']) ?></td>
            <td><?= status_badge((string)$rental['status']) ?><small class="d-block text-muted">Agreement: <?= e($rental['agreement_status']) ?></small></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-xl-6">
    <section class="panel table-panel h-100">
      <div class="panel-h d-flex justify-content-between">My payments and receipts <a class="small fw-normal" href="<?= e(url('invoices')) ?>">Invoices</a></div>
      <?php if (!$payments): ?><p class="text-muted mb-0">Your payments, deposits, and receipts will appear here.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th>Reference</th><th>Type</th><th>Amount</th><th>Status</th></tr></thead>
          <tbody><?php foreach ($payments as $payment): ?><tr <?= $payment['status'] === 'pending' && !empty($payment['external_reference']) ? 'data-payment-poll="' . e(url('payments/status', ['id' => $payment['transaction_id']])) . '"' : '' ?>>
            <td><?= e($payment['transaction_number']) ?><?php if (!empty($payment['external_reference'])): ?><small class="d-block text-muted"><?= e($payment['external_reference']) ?></small><?php endif; ?><small class="d-block text-muted"><?= e($payment['transaction_date']) ?></small></td>
            <td><?= e(ucwords(str_replace('_', ' ', $payment['transaction_type']))) ?></td>
            <td><?= e(\Crms\Money::format($payment['amount'])) ?></td>
            <td><?= status_badge((string)$payment['status']) ?>
              <?php if ($payment['status'] === 'completed' && in_array($payment['transaction_type'], ['payment','deposit_received','payment_refund','deposit_refund','deposit_deduction'], true)): ?>
                <a class="d-block small" href="<?= e(url('payments/receipt', ['id' => $payment['transaction_id']])) ?>">View receipt</a>
              <?php elseif ($payment['status'] === 'pending' && !empty($payment['external_reference'])): ?>
                <form method="post" action="<?= e(url('payments/sync', ['id' => $payment['transaction_id']])) ?>" class="mt-1"><?= \Crms\Csrf::field() ?><button class="btn btn-sm btn-outline-secondary">Check status</button></form>
              <?php elseif ($payment['invoice_id']): ?>
                <a class="d-block small" href="<?= e(url('invoices/show', ['id' => $payment['invoice_id']])) ?>">View invoice</a>
              <?php endif; ?>
            </td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
</div>

<section class="mt-4">
  <div class="d-flex justify-content-between align-items-end mb-3"><div><p class="eyebrow mb-1">Explore</p><h2 class="h5 mb-0">Available vehicles</h2></div><a href="<?= e(url('fleet')) ?>">Browse the fleet</a></div>
  <?php if (!$fleet): ?><p class="text-muted">No vehicles are available to browse right now.</p><?php else: ?>
    <div class="row g-3">
      <?php foreach ($fleet as $vehicle): ?><div class="col-md-6 col-xl-4">
        <article class="home-vehicle-card">
          <a class="home-vehicle-photo" href="<?= e(url('vehicles/show', ['id' => $vehicle['vehicle_id']])) ?>">
            <img src="<?= e(vehicle_img($vehicle['image_path'] ?? null, $vehicle['category_name'] ?? null)) ?>" alt="<?= e($vehicle['make'] . ' ' . $vehicle['model']) ?>">
            <span class="vehicle-tag"><?= e($vehicle['category_name']) ?></span>
          </a>
          <div class="home-vehicle-info">
            <div class="vehicle-title-row"><h3><?= e($vehicle['make'] . ' ' . $vehicle['model']) ?></h3></div>
            <div class="vehicle-bottom"><p class="home-vehicle-price"><strong><?= e(\Crms\Money::format($vehicle['daily_rate'])) ?></strong><span>/ day</span></p><a href="<?= e(url('vehicles/show', ['id' => $vehicle['vehicle_id']])) ?>">View details</a></div>
          </div>
        </article>
      </div><?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/partials/dashboard-notifications.php'; ?>
