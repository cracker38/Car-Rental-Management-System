<div class="card table-responsive"><table class="table mb-0">
  <thead><tr><th>Number</th><th>Customer</th><th>Total</th><th>Paid</th><th>Outstanding</th><th>Status</th></tr></thead>
  <tbody>
  <?php if (!$rows): ?><tr><td colspan="6" class="text-muted">No invoices.</td></tr><?php endif; ?>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><a href="<?= e(url('invoices/show', ['id' => $r['invoice_id']])) ?>"><?= e($r['invoice_number']) ?></a></td>
      <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
      <td><?= e(\Crms\Money::format($r['total_amount'])) ?></td>
      <td><?= e(\Crms\Money::format($r['net_paid'] ?? 0)) ?></td>
      <td><?= e(\Crms\Money::format($r['outstanding_balance'] ?? 0)) ?></td>
      <td><?= e($r['status']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
