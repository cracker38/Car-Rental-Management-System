<p><a class="btn btn-primary" href="<?= e(url('finance/form')) ?>">Post transaction</a></p>
<div class="card table-responsive"><table class="table mb-0">
  <thead><tr><th>Number</th><th>Customer</th><th>Type</th><th>Amount</th><th>Method</th><th>Ref</th><th>When</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['transaction_number']) ?></td>
      <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
      <td><?= e($r['transaction_type']) ?></td>
      <td><?= e(\Crms\Money::format($r['amount'])) ?></td>
      <td><?= e((string)$r['payment_method']) ?></td>
      <td><?= e((string)$r['external_reference']) ?></td>
      <td><?= e($r['transaction_date']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
