<?php
$dashboardSubtitle = 'Financial Management — invoices, payments, receipts, deposits, refunds, and reconciliation.';
$dashboardActions = [
  ['route' => 'invoices', 'label' => 'Invoices', 'icon' => 'bi-receipt'],
  ['route' => 'finance/form', 'label' => 'Post payment', 'icon' => 'bi-plus-lg'],
  ['route' => 'reports', 'label' => 'Finance reports', 'icon' => 'bi-graph-up'],
];
require dirname(__DIR__) . '/partials/dashboard-heading.php';
require dirname(__DIR__) . '/partials/kpis.php';
require dirname(__DIR__) . '/partials/dashboard-shortcuts.php';
?>
<div class="row g-3">
  <div class="col-xl-8">
    <section class="panel table-panel h-100">
      <div class="panel-h d-flex justify-content-between">Invoices needing attention <a class="small fw-normal" href="<?= e(url('invoices')) ?>">All invoices</a></div>
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
    <section class="panel h-100">
      <div class="panel-h d-flex justify-content-between">Pending MoMo <a class="small fw-normal" href="<?= e(url('finance')) ?>">Ledger</a></div>
      <?php if (!$pendingPay): ?><p class="text-muted mb-0">No payment requests are waiting.</p><?php else: ?>
        <?php foreach ($pendingPay as $payment): ?>
          <div class="mini-row" <?= $payment['external_reference'] ? 'data-payment-poll="' . e(url('payments/status', ['id' => $payment['transaction_id']])) . '"' : '' ?>>
            <strong><?= e($payment['transaction_number']) ?></strong>
            <span><?= e(\Crms\Money::format($payment['amount'])) ?> · <?= e($payment['external_reference'] ?: 'No HDEV ref') ?></span>
            <?php if ($payment['external_reference']): ?>
              <form method="post" action="<?= e(url('payments/sync', ['id' => $payment['transaction_id']])) ?>"><?= \Crms\Csrf::field() ?><button class="btn btn-sm btn-outline-secondary mt-1">Check HDEV</button></form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
      <div class="mini-row d-flex justify-content-between mt-3"><span>Held deposits</span><strong><?= e($deposits ?? '—') ?></strong></div>
    </section>
  </div>
  <div class="col-12">
    <section class="panel table-panel">
      <div class="panel-h">Overdue invoices</div>
      <?php if (empty($overdue)): ?><p class="text-muted mb-0">No overdue balances.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th>Invoice</th><th>Customer</th><th>Due</th><th>Balance</th></tr></thead>
          <tbody><?php foreach ($overdue as $item): ?><tr>
            <td><a href="<?= e(url('invoices/show', ['id' => $item['invoice_id']])) ?>"><?= e($item['invoice_number']) ?></a></td>
            <td><?= e($item['first_name'] . ' ' . $item['last_name']) ?></td>
            <td><?= e($item['due_date']) ?></td>
            <td><?= e(\Crms\Money::format($item['outstanding_balance'] ?? 0)) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php require dirname(__DIR__) . '/partials/dashboard-notifications.php'; ?>
