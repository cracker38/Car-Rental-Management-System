<div class="row g-4">
  <div class="col-lg-6">
    <form method="post" class="panel">
      <?= \Crms\Csrf::field() ?>
      <p class="eyebrow">Ledger</p>
      <h2 class="h4">Record a payment or request approval</h2>
      <p class="text-muted">Payments and received deposits post immediately. Refunds, deposit deductions and adjustments wait for an authorized independent reviewer. Their balances change only after approval.</p>
      <?php if ($invoice): ?>
        <div class="alert alert-info">Invoice <?= e($invoice['invoice_number']) ?> outstanding <?= e(\Crms\Money::format($invoice['outstanding_balance'] ?? 0)) ?></div>
        <input type="hidden" name="invoice_id" value="<?= (int)$invoice['invoice_id'] ?>">
        <input type="hidden" name="customer_id" value="<?= (int)$invoice['customer_id'] ?>">
      <?php else: ?>
        <div class="mb-3"><label class="form-label" for="transaction-customer">Customer</label>
          <select class="form-select" id="transaction-customer" name="customer_id" required>
            <?php foreach ($customers as $c): ?>
              <option value="<?= (int)$c['customer_id'] ?>"><?= e($c['customer_code'] . ' · ' . $c['first_name'] . ' ' . $c['last_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label" for="transaction-invoice">Invoice (required for payments and adjustments)</label>
          <select class="form-select" id="transaction-invoice" name="invoice_id">
            <option value="">Choose invoice when applicable</option>
            <?php foreach ($openInvoices as $choice): ?>
              <option value="<?= (int) $choice['invoice_id'] ?>" data-customer-id="<?= (int) $choice['customer_id'] ?>"><?= e($choice['invoice_number'] . ' · ' . $choice['first_name'] . ' ' . $choice['last_name'] . ' · ' . \Crms\Money::format($choice['outstanding_balance'])) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Type</label>
          <select class="form-select" id="transaction-type" name="transaction_type" required>
            <?php foreach (['payment'=>'Payment','payment_refund'=>'Refund / reversal','deposit_received'=>'Deposit received','deposit_deduction'=>'Deposit deduction','deposit_refund'=>'Deposit refund','adjustment'=>'Adjustment'] as $val=>$lab): ?>
              <option value="<?= $val ?>"><?= $lab ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6"><label class="form-label">Amount (RWF)</label><input class="form-control" name="amount" required></div>
        <div class="col-md-6 d-none" id="adjustment-direction-wrap"><label class="form-label" for="adjustment-direction">Adjustment direction</label>
          <select class="form-select" id="adjustment-direction" name="adjustment_direction">
            <option value="">Choose direction</option><option value="credit">Credit customer (reduces invoice balance)</option><option value="debit">Debit customer (increases invoice balance)</option>
          </select>
        </div>
        <div class="col-md-6"><label class="form-label">Method</label>
          <select class="form-select" id="transaction-method" name="payment_method" required>
            <option value="">Choose method</option>
            <?php foreach (['cash'=>'Cash','bank_transfer'=>'Bank transfer','card'=>'Card','mobile_money'=>'Mobile money','online'=>'Online','other'=>'Other'] as $val=>$lab): ?>
              <option value="<?= $val ?>"><?= $lab ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6"><label class="form-label" for="external-reference">External reference</label><input class="form-control" id="external-reference" name="external_reference" placeholder="Required for bank, card, MoMo or online"></div>
        <div class="col-md-6"><label class="form-label">Original payment (for refunds)</label>
          <select class="form-select" id="related-payment" name="related_transaction_id">
            <option value="">—</option>
            <?php foreach ($payments as $p): ?>
              <option value="<?= (int)$p['transaction_id'] ?>" data-customer-id="<?= (int) $p['customer_id'] ?>" data-invoice-id="<?= (int) ($p['invoice_id'] ?? 0) ?>"><?= e($p['transaction_number'] . ' · ' . $p['amount']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6"><label class="form-label">Rental ID (optional)</label><input class="form-control" name="rental_id"></div>
        <div class="col-12"><label class="form-label" for="transaction-reason">Reason</label><input class="form-control" id="transaction-reason" name="reason" placeholder="Required for refunds, deductions and adjustments"></div>
      </div>
      <p class="small text-muted mt-3 mb-0">The system records the posting date and time. An approved request receives its final posting date when the reviewer approves it.</p>
      <button class="btn btn-primary mt-3" id="transaction-submit">Post payment</button>
    </form>
  </div>
  <div class="col-lg-6">
    <form method="post" action="<?= e(url('payments/momo')) ?>" class="panel momo-card">
      <?= \Crms\Csrf::field() ?>
      <p class="eyebrow">PawaPay</p>
      <h2 class="h4">Request Mobile Money</h2>
      <p class="text-muted">Creates a pending ledger row. It completes after the customer approves the prompt and PawaPay confirms.</p>
      <?php if ($invoice): ?>
        <input type="hidden" name="invoice_id" value="<?= (int)$invoice['invoice_id'] ?>">
        <input type="hidden" name="customer_id" value="<?= (int)$invoice['customer_id'] ?>">
      <?php else: ?>
        <div class="mb-3"><label class="form-label" for="momo-customer">Customer</label>
          <select class="form-select" id="momo-customer" name="customer_id" required>
            <?php foreach ($customers as $c): ?>
              <option value="<?= (int)$c['customer_id'] ?>"><?= e($c['customer_code'] . ' · ' . $c['first_name'] . ' ' . $c['last_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label" for="momo-invoice">Invoice (required for payment)</label>
          <select class="form-select" id="momo-invoice" name="invoice_id">
            <option value="">Choose invoice for payment</option>
            <?php foreach ($openInvoices as $choice): ?>
              <option value="<?= (int) $choice['invoice_id'] ?>" data-customer-id="<?= (int) $choice['customer_id'] ?>"><?= e($choice['invoice_number'] . ' · ' . $choice['first_name'] . ' ' . $choice['last_name'] . ' · ' . \Crms\Money::format($choice['outstanding_balance'])) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
      <div class="mb-3"><label class="form-label">Type</label>
        <select class="form-select" id="momo-type" name="transaction_type">
          <option value="payment">Invoice payment</option>
          <option value="deposit_received">Security deposit</option>
        </select>
      </div>
      <div class="mb-3"><label class="form-label">MoMo phone</label><input class="form-control" name="phone" required placeholder="07xxxxxxxx" inputmode="tel"></div>
      <div class="mb-3"><label class="form-label">Mobile network</label><select class="form-select" name="provider" required><option value="">Choose network</option><option value="MTN_MOMO_RWA">MTN</option><option value="AIRTEL_RWA">Airtel</option></select></div>
      <div class="mb-3"><label class="form-label">Amount (RWF)</label><input class="form-control" name="amount" required value="<?= e((string)((int)($invoice['outstanding_balance'] ?? 0))) ?>"></div>
      <button class="btn btn-primary">Send MoMo prompt</button>
    </form>
  </div>
</div>
<script>
(() => {
  const type = document.getElementById('transaction-type');
  if (!type) return;
  const directionWrap = document.getElementById('adjustment-direction-wrap');
  const direction = document.getElementById('adjustment-direction');
  const method = document.getElementById('transaction-method');
  const reference = document.getElementById('external-reference');
  const reason = document.getElementById('transaction-reason');
  const submit = document.getElementById('transaction-submit');
  const invoiceSelect = document.getElementById('transaction-invoice');
  const customerSelect = document.getElementById('transaction-customer');
  const relatedPayment = document.getElementById('related-payment');
  const syncFinanceInvoice = () => {
    const option = invoiceSelect?.selectedOptions[0];
    if (customerSelect && option?.dataset.customerId) customerSelect.value = option.dataset.customerId;
  };
  invoiceSelect?.addEventListener('change', syncFinanceInvoice);
  relatedPayment?.addEventListener('change', () => {
    const option = relatedPayment.selectedOptions[0];
    if (customerSelect && option?.dataset.customerId) customerSelect.value = option.dataset.customerId;
    if (invoiceSelect && option?.dataset.invoiceId) {
      const linkedInvoice = [...invoiceSelect.options].find((item) => item.value === option.dataset.invoiceId);
      invoiceSelect.value = linkedInvoice ? linkedInvoice.value : '';
    }
  });
  const refresh = () => {
    const needsApproval = ['payment_refund', 'deposit_deduction', 'deposit_refund', 'adjustment'].includes(type.value);
    const adjustment = type.value === 'adjustment';
    directionWrap.classList.toggle('d-none', !adjustment);
    direction.required = adjustment;
    reason.required = needsApproval;
    reference.required = ['bank_transfer', 'card', 'mobile_money', 'online'].includes(method.value);
    if (invoiceSelect) invoiceSelect.required = ['payment', 'adjustment'].includes(type.value);
    submit.textContent = needsApproval ? 'Submit for approval' : 'Post payment';
  };
  type.addEventListener('change', refresh);
  method.addEventListener('change', refresh);
  refresh();

  const momoType = document.getElementById('momo-type');
  const momoInvoice = document.getElementById('momo-invoice');
  const momoCustomer = document.getElementById('momo-customer');
  momoInvoice?.addEventListener('change', () => {
    const option = momoInvoice.selectedOptions[0];
    if (momoCustomer && option?.dataset.customerId) momoCustomer.value = option.dataset.customerId;
  });
  const refreshMomo = () => {
    if (momoInvoice && momoType) momoInvoice.required = momoType.value === 'payment';
  };
  momoType?.addEventListener('change', refreshMomo);
  refreshMomo();
})();
</script>
