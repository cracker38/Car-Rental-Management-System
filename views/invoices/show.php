<div class="detail-grid">
  <?php $bookingPaymentBlocked = strncmp((string) ($row['notes'] ?? ''), 'Booking prepayment:', 19) === 0 && (($row['reservation_status'] ?? '') !== 'confirmed' || trim((string) ($row['approval_note'] ?? '')) === '' || empty($row['approver_signature']) || empty($row['customer_signature']) || empty($row['customer_signed_at']) || empty($row['customer_signature_user_id']) || (int) $row['customer_signature_user_id'] !== (int) ($row['customer_user_id'] ?? 0)); ?>
  <section class="panel invoice-hero">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
      <div>
        <p class="eyebrow mb-1">Invoice</p>
        <h2><?= e($row['invoice_number']) ?></h2>
        <p class="mb-0 text-muted"><?= e($row['first_name'] . ' ' . $row['last_name']) ?> · issued <?= e($row['issue_date']) ?></p>
      </div>
      <?= status_badge((string) $row['status']) ?>
    </div>
    <div class="kpi-row">
      <div><span>Total</span><strong><?= e(\Crms\Money::format($row['total_amount'])) ?></strong></div>
      <div><span>Paid</span><strong><?= e(\Crms\Money::format($row['net_paid'] ?? 0)) ?></strong></div>
      <div><span>Outstanding</span><strong><?= e(\Crms\Money::format($row['outstanding_balance'] ?? 0)) ?></strong></div>
    </div>
    <?php if ($row['status'] === 'draft' && can('invoices.manage')): ?>
      <form method="post" action="<?= e(url('invoices/issue', ['id' => $row['invoice_id']])) ?>"><?= \Crms\Csrf::field() ?><button class="btn btn-primary">Issue invoice</button></form>
    <?php endif; ?>
  </section>

  <?php if (!in_array($row['status'], ['draft','void','paid'], true) && (float)($row['outstanding_balance'] ?? 0) > 0 && !$bookingPaymentBlocked && ($paymentRequestable ?? 0) > 0 && (can('transactions.manage') || \Crms\Auth::isCustomer())): ?>
  <form method="post" action="<?= e(url('payments/momo')) ?>" class="panel momo-card">
    <?= \Crms\Csrf::field() ?>
    <input type="hidden" name="invoice_id" value="<?= (int)$row['invoice_id'] ?>">
    <input type="hidden" name="customer_id" value="<?= (int)$row['customer_id'] ?>">
    <input type="hidden" name="transaction_type" value="payment">
    <p class="eyebrow">Mobile Money</p>
    <h3>Pay with MTN or Airtel</h3>
    <p class="text-muted">A prompt is sent to the phone. The invoice stays unpaid until HDEV confirms.</p>
    <div class="row g-3">
      <div class="col-md-5"><label class="form-label">MoMo phone</label><input class="form-control" name="phone" required value="<?= e((string)$row['phone']) ?>" placeholder="07xxxxxxxx" inputmode="tel"></div>
      <div class="col-md-4"><label class="form-label">Amount available (RWF)</label><input class="form-control" name="amount" required value="<?= e((string)((int)($paymentRequestable ?? 0))) ?>"></div>
      <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary w-100">Send prompt</button></div>
    </div>
  </form>
  <?php elseif (!$bookingPaymentBlocked && (float) ($row['outstanding_balance'] ?? 0) > 0 && (float) ($pendingMomoAmount ?? 0) > 0): ?>
    <section class="panel"><p class="mb-0">Mobile Money confirmation is pending for <?= e(\Crms\Money::format($pendingMomoAmount)) ?>. Use Check status below before starting another request.</p></section>
  <?php elseif ($bookingPaymentBlocked && (float) ($row['outstanding_balance'] ?? 0) > 0): ?>
    <section class="panel"><p class="mb-0">This booking invoice can be paid after the signed reservation request receives an approval decision.</p></section>
  <?php endif; ?>
</div>

<div class="panel table-panel">
  <div class="panel-h">Line items</div>
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Type</th><th>Description</th><th>Qty</th><th>Unit</th><th>Line</th></tr></thead>
    <tbody>
    <?php foreach ($items as $i): ?>
      <tr>
        <td><?= e(str_replace('_', ' ', $i['item_type'])) ?></td>
        <td><?= e($i['description']) ?></td>
        <td><?= e($i['quantity']) ?></td>
        <td><?= e(\Crms\Money::format($i['unit_price'])) ?></td>
        <td><?= e(\Crms\Money::format($i['line_total'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<?php if (can('transactions.manage') && !in_array($row['status'], ['draft','void'], true)): ?>
  <p><a class="btn btn-outline-dark" href="<?= e(url('finance/form', ['invoice_id' => $row['invoice_id']])) ?>">Post cash / bank / refund</a></p>
<?php endif; ?>

<div class="panel table-panel">
  <div class="panel-h">Payment activity</div>
  <?php if (!$txns): ?><p class="text-muted mb-0">No payments yet.</p>
  <?php else: ?>
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Reference</th><th>Type</th><th>Amount</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($txns as $t): ?>
      <tr <?= $t['status'] === 'pending' && $t['external_reference'] ? 'data-payment-poll="' . e(url('payments/status', ['id' => $t['transaction_id']])) . '"' : '' ?>>
        <td><?= e($t['transaction_number']) ?><?php if ($t['external_reference']): ?><div class="small text-muted"><?= e($t['external_reference']) ?></div><?php endif; ?></td>
        <td><?= e(str_replace('_', ' ', $t['transaction_type'])) ?><?php if ($t['adjustment_direction']): ?><small class="d-block text-muted"><?= e(ucfirst($t['adjustment_direction'])) ?> adjustment</small><?php endif; ?><?php if ($t['reason']): ?><small class="d-block text-muted"><?= e(substr((string) $t['reason'], 0, 100)) ?></small><?php endif; ?><?php if ($t['status'] === 'rejected' && $t['approval_note']): ?><small class="d-block text-danger">Rejected: <?= e(substr((string) $t['approval_note'], 0, 100)) ?></small><?php endif; ?></td>
        <td><?= e(\Crms\Money::format($t['amount'])) ?></td>
        <td><?= status_badge((string)$t['status']) ?></td>
        <td class="text-nowrap">
          <?php if ($t['status'] === 'pending' && $t['external_reference']): ?>
            <form method="post" action="<?= e(url('payments/sync', ['id' => $t['transaction_id']])) ?>" class="d-inline"><?= \Crms\Csrf::field() ?><button class="btn btn-sm btn-outline-secondary">Check status</button></form>
          <?php endif; ?>
          <?php if ($t['status'] === 'completed' && in_array($t['transaction_type'], ['payment','deposit_received','payment_refund','deposit_refund','deposit_deduction'], true)): ?><a class="btn btn-sm btn-outline-dark" href="<?= e(url('payments/receipt', ['id' => $t['transaction_id']])) ?>">Receipt</a><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
