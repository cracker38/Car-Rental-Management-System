<div class="card card-body mb-3">
  <h2 class="h5"><?= e($row['invoice_number']) ?></h2>
  <p><?= e($row['first_name'] . ' ' . $row['last_name']) ?> · <?= e($row['issue_date']) ?> · <?= e($row['status']) ?></p>
  <p>Subtotal <?= e(\Crms\Money::format($row['subtotal'])) ?> · Discount <?= e(\Crms\Money::format($row['discount_amount'])) ?> · Tax <?= e(\Crms\Money::format($row['tax_amount'])) ?></p>
  <p class="mb-0"><strong>Total <?= e(\Crms\Money::format($row['total_amount'])) ?></strong> · Outstanding <?= e(\Crms\Money::format($row['outstanding_balance'] ?? 0)) ?></p>
</div>
<table class="table">
  <thead><tr><th>Type</th><th>Description</th><th>Qty</th><th>Unit</th><th>Line</th></tr></thead>
  <tbody>
  <?php foreach ($items as $i): ?>
    <tr>
      <td><?= e($i['item_type']) ?></td>
      <td><?= e($i['description']) ?></td>
      <td><?= e($i['quantity']) ?></td>
      <td><?= e(\Crms\Money::format($i['unit_price'])) ?></td>
      <td><?= e(\Crms\Money::format($i['line_total'])) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php if ($row['status'] === 'draft' && can('invoices.manage')): ?>
  <form method="post" action="<?= e(url('invoices/issue', ['id' => $row['invoice_id']])) ?>"><?= \Crms\Csrf::field() ?><button class="btn btn-primary">Issue invoice</button></form>
<?php endif; ?>
<?php if (can('transactions.manage') && !in_array($row['status'], ['draft','void'], true)): ?>
  <a class="btn btn-outline-primary" href="<?= e(url('finance/form', ['invoice_id' => $row['invoice_id']])) ?>">Post payment / refund</a>
<?php endif; ?>
<?php if (!in_array($row['status'], ['draft','void','paid'], true) && (float)($row['outstanding_balance'] ?? 0) > 0 && (can('transactions.manage') || \Crms\Auth::isCustomer())): ?>
<form method="post" action="<?= e(url('payments/momo')) ?>" class="card card-body my-3">
  <?= \Crms\Csrf::field() ?>
  <input type="hidden" name="invoice_id" value="<?= (int)$row['invoice_id'] ?>">
  <input type="hidden" name="customer_id" value="<?= (int)$row['customer_id'] ?>">
  <input type="hidden" name="transaction_type" value="payment">
  <h3 class="h6">Pay with MTN/Airtel MoMo (HDEV)</h3>
  <div class="row g-2">
    <div class="col-md-4"><label class="form-label">MoMo phone</label><input class="form-control" name="phone" required value="<?= e((string)$row['phone']) ?>" placeholder="07xxxxxxxx"></div>
    <div class="col-md-4"><label class="form-label">Amount (RWF)</label><input class="form-control" name="amount" required value="<?= e((string)((int)($row['outstanding_balance'] ?? 0))) ?>"></div>
    <div class="col-md-4 d-flex align-items-end"><button class="btn btn-success">Send MoMo prompt</button></div>
  </div>
  <p class="small text-muted mb-0 mt-2">The invoice is not marked paid until HDEV confirms the transaction. Pending rows do not change the balance.</p>
</form>
<?php endif; ?>
<h3 class="h6 mt-4">Ledger</h3>
<ul>
  <?php foreach ($txns as $t): ?>
    <li>
      <?= e($t['transaction_number']) ?> · <?= e($t['transaction_type']) ?> · <?= e(\Crms\Money::format($t['amount'])) ?> · <?= e($t['status']) ?>
      <?php if ($t['status'] === 'pending' && $t['external_reference']): ?>
        <form method="post" action="<?= e(url('payments/sync', ['id' => $t['transaction_id']])) ?>" class="d-inline"><?= \Crms\Csrf::field() ?><button class="btn btn-sm btn-outline-secondary">Check HDEV</button></form>
      <?php endif; ?>
      <?php if ($t['status'] === 'completed' && in_array($t['transaction_type'], ['payment','deposit_received','payment_refund','deposit_refund','deposit_deduction'], true)): ?><a class="btn btn-sm btn-outline-dark" href="<?= e(url('payments/receipt', ['id' => $t['transaction_id']])) ?>">Receipt</a><?php endif; ?>
    </li>
  <?php endforeach; ?>
</ul>
