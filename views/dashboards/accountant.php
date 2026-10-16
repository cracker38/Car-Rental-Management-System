<?php
$dashboardSubtitle = 'Track unpaid invoices, Mobile Money requests, and today’s receipts.';
$dashboardActions = [
  ['route' => 'invoices', 'label' => 'Invoices', 'icon' => 'bi-receipt'],
  ['route' => 'finance', 'label' => 'Payments', 'icon' => 'bi-wallet2'],
  ['route' => 'reports', 'label' => 'Financial reports', 'icon' => 'bi-graph-up'],
];
require dirname(__DIR__) . '/partials/dashboard-heading.php';
require dirname(__DIR__) . '/partials/kpis.php';
?>
<div class="row g-4">
  <div class="col-xl-8">
    <section class="card card-body h-100">
      <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Invoices needing attention</h2><a href="<?= e(url('invoices')) ?>">All invoices</a></div>
      <?php if (!$invoices): ?><p class="text-muted mb-0">No invoices need attention.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th>Invoice</th><th>Customer</th><th>Total</th><th>Balance</th><th>Status</th></tr></thead>
          <tbody><?php foreach ($invoices as $item): ?><tr>
            <td><a href="<?= e(url('invoices/show', ['id' => $item['invoice_id']])) ?>"><?= e($item['invoice_number']) ?></a></td>
            <td><?= e($item['first_name'] . ' ' . $item['last_name']) ?></td><td><?= e(\Crms\Money::format($item['total_amount'])) ?></td>
            <td><?= e(\Crms\Money::format($item['outstanding_balance'] ?? 0)) ?></td><td><?= status_badge((string)$item['status']) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-xl-4">
    <section class="card card-body h-100">
      <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Pending payments</h2><a href="<?= e(url('finance')) ?>">Payments</a></div>
      <?php if (!$pendingPay): ?><p class="text-muted mb-0">No payment requests are waiting.</p><?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($pendingPay as $payment): ?><li class="list-group-item px-0 d-flex justify-content-between gap-2">
            <span><strong><?= e($payment['transaction_number']) ?></strong><small class="d-block text-muted"><?= e($payment['transaction_date']) ?></small></span>
            <span class="text-end"><?= e(\Crms\Money::format($payment['amount'])) ?><small class="d-block text-muted"><?= e($payment['status']) ?></small></span>
          </li><?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php require dirname(__DIR__) . '/partials/dashboard-notifications.php'; ?>
