<?php if (!empty($unbilledRentals) && can('invoices.manage')): ?>
  <section class="panel table-panel mb-3">
    <div class="panel-h">Closed rentals without invoices</div>
    <p class="small text-muted">Generate draft invoices from completed rental charges, then review and issue them through the invoice workflow.</p>
    <div class="table-responsive"><table class="table align-middle mb-0">
      <thead><tr><th>Rental</th><th>Customer</th><th>Returned</th><th></th></tr></thead>
      <tbody><?php foreach ($unbilledRentals as $rental): ?><tr>
        <td><?= e($rental['rental_number']) ?></td>
        <td><?= e($rental['first_name'] . ' ' . $rental['last_name']) ?></td>
        <td><?= e($rental['actual_return_datetime']) ?></td>
        <td><form method="post" action="<?= e(url('invoices/create')) ?>" class="m-0"><?= \Crms\Csrf::field() ?><input type="hidden" name="rental_id" value="<?= (int) $rental['rental_id'] ?>"><button class="btn btn-sm btn-outline-primary">Generate draft</button></form></td>
      </tr><?php endforeach; ?></tbody>
    </table></div>
  </section>
<?php endif; ?>
<div class="panel table-panel">
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Number</th><th>Customer</th><th>Total</th><th>Paid</th><th>Outstanding</th><th>Status</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="6"><div class="empty-state">No invoices yet.</div></td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><a href="<?= e(url('invoices/show', ['id' => $r['invoice_id']])) ?>"><?= e($r['invoice_number']) ?></a></td>
        <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
        <td><?= e(\Crms\Money::format($r['total_amount'])) ?></td>
        <td><?= e(\Crms\Money::format($r['net_paid'] ?? 0)) ?></td>
        <td><?= e(\Crms\Money::format($r['outstanding_balance'] ?? 0)) ?></td>
        <td><?= status_badge((string)$r['status']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
