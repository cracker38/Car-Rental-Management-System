<form method="post" class="card card-body">
  <?= \Crms\Csrf::field() ?>
  <?php if ($invoice): ?>
    <div class="alert alert-info">Invoice <?= e($invoice['invoice_number']) ?> outstanding <?= e(\Crms\Money::format($invoice['outstanding_balance'] ?? 0)) ?></div>
    <input type="hidden" name="invoice_id" value="<?= (int)$invoice['invoice_id'] ?>">
    <input type="hidden" name="customer_id" value="<?= (int)$invoice['customer_id'] ?>">
  <?php else: ?>
    <div class="mb-3"><label class="form-label">Customer</label>
      <select class="form-select" name="customer_id" required>
        <?php foreach ($customers as $c): ?>
          <option value="<?= (int)$c['customer_id'] ?>"><?= e($c['customer_code'] . ' · ' . $c['first_name'] . ' ' . $c['last_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3"><label class="form-label">Invoice ID (optional)</label><input class="form-control" name="invoice_id"></div>
  <?php endif; ?>
  <div class="row g-3">
    <div class="col-md-4"><label class="form-label">Type</label>
      <select class="form-select" name="transaction_type" required>
        <?php foreach (['payment','payment_refund','deposit_received','deposit_deduction','deposit_refund','adjustment'] as $t): ?>
          <option value="<?= $t ?>"><?= $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4"><label class="form-label">Amount (RWF)</label><input class="form-control" name="amount" required></div>
    <div class="col-md-4"><label class="form-label">Method</label>
      <select class="form-select" name="payment_method">
        <option value="">—</option>
        <?php foreach (['cash','bank_transfer','card','mobile_money','online','other'] as $t): ?>
          <option value="<?= $t ?>"><?= $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6"><label class="form-label">External reference (idempotency key)</label><input class="form-control" name="external_reference"></div>
    <div class="col-md-6"><label class="form-label">Original payment (for refunds)</label>
      <select class="form-select" name="related_transaction_id">
        <option value="">—</option>
        <?php foreach ($payments as $p): ?>
          <option value="<?= (int)$p['transaction_id'] ?>"><?= e($p['transaction_number'] . ' · ' . $p['amount']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6"><label class="form-label">Rental ID (optional, deposits)</label><input class="form-control" name="rental_id"></div>
    <div class="col-12"><label class="form-label">Reason</label><input class="form-control" name="reason"></div>
  </div>
  <p class="small text-muted mt-2">Posted ledger rows are immutable. Duplicate external references are rejected.</p>
  <button class="btn btn-primary mt-2">Post</button>
</form>
