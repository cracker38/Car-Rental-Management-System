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
<h3 class="h6 mt-4">Ledger</h3>
<ul>
  <?php foreach ($txns as $t): ?>
    <li><?= e($t['transaction_number']) ?> · <?= e($t['transaction_type']) ?> · <?= e(\Crms\Money::format($t['amount'])) ?> · <?= e($t['status']) ?></li>
  <?php endforeach; ?>
</ul>
