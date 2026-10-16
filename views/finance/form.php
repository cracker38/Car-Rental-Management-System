<div class="row g-4">
  <div class="col-lg-6">
    <form method="post" class="panel">
      <?= \Crms\Csrf::field() ?>
      <p class="eyebrow">Ledger</p>
      <h2 class="h4">Record a completed payment</h2>
      <p class="text-muted">Use this for cash, bank, card, or refunds that already happened. Posted rows cannot be edited.</p>
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
        <div class="col-md-6"><label class="form-label">Type</label>
          <select class="form-select" name="transaction_type" required>
            <?php foreach (['payment'=>'Payment','payment_refund'=>'Refund','deposit_received'=>'Deposit received','deposit_deduction'=>'Deposit deduction','deposit_refund'=>'Deposit refund','adjustment'=>'Adjustment'] as $val=>$lab): ?>
              <option value="<?= $val ?>"><?= $lab ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6"><label class="form-label">Amount (RWF)</label><input class="form-control" name="amount" required></div>
        <div class="col-md-6"><label class="form-label">Method</label>
          <select class="form-select" name="payment_method">
            <option value="">—</option>
            <?php foreach (['cash'=>'Cash','bank_transfer'=>'Bank transfer','card'=>'Card','mobile_money'=>'Mobile money','online'=>'Online','other'=>'Other'] as $val=>$lab): ?>
              <option value="<?= $val ?>"><?= $lab ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6"><label class="form-label">External reference</label><input class="form-control" name="external_reference"></div>
        <div class="col-md-6"><label class="form-label">Original payment (for refunds)</label>
          <select class="form-select" name="related_transaction_id">
            <option value="">—</option>
            <?php foreach ($payments as $p): ?>
              <option value="<?= (int)$p['transaction_id'] ?>"><?= e($p['transaction_number'] . ' · ' . $p['amount']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6"><label class="form-label">Rental ID (optional)</label><input class="form-control" name="rental_id"></div>
        <div class="col-12"><label class="form-label">Reason</label><input class="form-control" name="reason"></div>
      </div>
      <button class="btn btn-primary mt-3">Post ledger</button>
    </form>
  </div>
  <div class="col-lg-6">
    <form method="post" action="<?= e(url('payments/momo')) ?>" class="panel momo-card">
      <?= \Crms\Csrf::field() ?>
      <p class="eyebrow">HDEV</p>
      <h2 class="h4">Request Mobile Money</h2>
      <p class="text-muted">Creates a pending ledger row. Completes only after the customer approves the prompt and HDEV confirms.</p>
      <?php if ($invoice): ?>
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
      <div class="mb-3"><label class="form-label">Type</label>
        <select class="form-select" name="transaction_type">
          <option value="payment">Invoice payment</option>
          <option value="deposit_received">Security deposit</option>
        </select>
      </div>
      <div class="mb-3"><label class="form-label">MoMo phone</label><input class="form-control" name="phone" required placeholder="07xxxxxxxx" inputmode="tel"></div>
      <div class="mb-3"><label class="form-label">Amount (RWF)</label><input class="form-control" name="amount" required value="<?= e((string)((int)($invoice['outstanding_balance'] ?? 0))) ?>"></div>
      <button class="btn btn-primary">Send MoMo prompt</button>
    </form>
  </div>
</div>
