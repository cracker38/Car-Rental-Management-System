<?php
$dashboardEyebrow = 'Finance and billing';
$dashboardHeading = 'Financial Management';
$dashboardSubtitle = 'Invoices, rental charges, payment collection, deposits and reconciliation.';
$dashboardActions = [];
if (can('invoices.view')) {
  $dashboardActions[] = ['route' => 'invoices', 'label' => 'Invoice workflow', 'icon' => 'bi-receipt'];
}
if (can('transactions.manage')) {
  $dashboardActions[] = ['route' => 'finance/form', 'label' => 'Record transaction', 'icon' => 'bi-plus-lg'];
}
if (can('transactions.view')) {
  $dashboardActions[] = ['route' => 'finance', 'label' => 'Reconcile ledger', 'icon' => 'bi-journal-check'];
}
if (can('reports.financial')) {
  $dashboardActions[] = ['route' => 'reports', 'label' => 'Finance reports', 'icon' => 'bi-graph-up'];
}
require dirname(__DIR__) . '/partials/dashboard-heading.php';
$kpiColClass = count($kpis) >= 5 ? 'col-6 col-md-4 col-xl' : 'col-6 col-lg-3';
require dirname(__DIR__) . '/partials/kpis.php';
?>
<div class="row g-3 mb-3">
  <div class="col-xl-7">
    <section class="panel table-panel h-100">
      <div class="panel-h d-flex justify-content-between gap-2"><span>Invoice workflow</span><a class="small fw-normal" href="<?= e(url('invoices')) ?>">All invoices</a></div>
      <p class="small text-muted">Open an invoice to review rental charges, issue a draft, or review payment history.</p>
      <?php if ($unbilledRentals): ?>
        <div class="manager-subsection-heading">Closed rentals without invoices</div>
        <?php foreach ($unbilledRentals as $rental): ?><div class="mini-row d-flex justify-content-between align-items-center gap-2">
          <span><strong><?= e($rental['rental_number']) ?></strong><small class="d-block text-muted"><?= e($rental['first_name'] . ' ' . $rental['last_name']) ?> &middot; <?= e($rental['actual_return_datetime']) ?></small>
            <small class="d-block">Review charges: mileage <?= e(\Crms\Money::format($rental['extra_mileage_fee'])) ?>, late <?= e(\Crms\Money::format($rental['late_return_fee'])) ?>, damage <?= e(\Crms\Money::format($rental['damage_fee'])) ?>, cleaning <?= e(\Crms\Money::format($rental['cleaning_fee'])) ?>, other <?= e(\Crms\Money::format($rental['other_charges'])) ?></small>
            <?php if (!empty($rental['return_notes'])): ?><small class="d-block text-muted">Handover notes: <?= e($rental['return_notes']) ?></small><?php endif; ?>
            <?php if ($rental['actual_return_datetime'] > $rental['expected_return_datetime']): ?><span class="badge text-bg-warning mt-1">Late return review</span><?php endif; ?>
          </span>
          <form method="post" action="<?= e(url('invoices/create')) ?>" class="m-0"><?= \Crms\Csrf::field() ?><input type="hidden" name="rental_id" value="<?= (int) $rental['rental_id'] ?>"><button class="btn btn-sm btn-outline-primary">Create draft</button></form>
        </div><?php endforeach; ?>
      <?php endif; ?>
      <?php if (!$invoices): ?><p class="text-muted mb-0">No invoices need attention.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th scope="col">Invoice</th><th scope="col">Customer</th><th scope="col">Total</th><th scope="col">Balance</th><th scope="col">Status</th></tr></thead>
          <tbody><?php foreach ($invoices as $item): ?><tr>
            <td><a href="<?= e(url('invoices/show', ['id' => $item['invoice_id']])) ?>"><?= e($item['invoice_number']) ?></a></td>
            <td><?= e($item['first_name'] . ' ' . $item['last_name']) ?></td>
            <td><?= e(\Crms\Money::format($item['total_amount'])) ?></td>
            <td><?= e(\Crms\Money::format($item['outstanding_balance'] ?? 0)) ?></td>
            <td><?= status_badge((string) $item['status']) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-xl-5">
    <section class="panel h-100">
      <div class="panel-h d-flex justify-content-between gap-2"><span>Mobile Money awaiting confirmation</span><a class="small fw-normal" href="<?= e(url('finance')) ?>">Payment ledger</a></div>
      <p class="small text-muted">Pending requests do not change invoice balances until the provider confirms payment.</p>
      <?php if (!$pendingPay): ?><p class="text-muted mb-0">No payment requests are waiting.</p><?php else: ?>
        <?php foreach ($pendingPay as $payment): ?>
          <div class="mini-row" <?= $payment['external_reference'] ? 'data-payment-poll="' . e(url('payments/status', ['id' => $payment['transaction_id']])) . '"' : '' ?>>
            <strong><?= e($payment['transaction_number']) ?></strong>
            <span><?= e(\Crms\Money::format($payment['amount'])) ?> &middot; <?= e($payment['external_reference'] ?: 'No provider reference') ?></span>
            <?php if ($payment['external_reference']): ?>
              <form method="post" action="<?= e(url('payments/sync', ['id' => $payment['transaction_id']])) ?>"><?= \Crms\Csrf::field() ?><button class="btn btn-sm btn-outline-secondary mt-1">Check payment status</button></form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
    <section class="panel mt-3">
      <div class="panel-h d-flex justify-content-between gap-2"><span>Refunds and adjustments awaiting review</span><a class="small fw-normal" href="<?= e(url('finance')) ?>">Ledger</a></div>
      <p class="small text-muted">These requests will affect invoice or deposit balances only after an independent reviewer approves them.</p>
      <?php if (!$pendingApproval): ?><p class="text-muted mb-0">No requests are awaiting review.</p><?php else: ?>
        <?php foreach ($pendingApproval as $request): ?>
          <div class="mini-row d-flex justify-content-between gap-3">
            <span><strong><?= e($request['transaction_number']) ?></strong><small class="d-block text-muted"><?= e(ucwords(str_replace('_', ' ', $request['transaction_type']))) ?> &middot; <?= e($request['created_at']) ?></small></span>
            <strong class="text-nowrap"><?= e(\Crms\Money::format($request['amount'])) ?></strong>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php require dirname(__DIR__) . '/partials/dashboard-notifications.php'; ?>

<div class="row g-3 mb-3">
  <div class="col-lg-5">
    <section class="panel table-panel h-100">
      <div class="panel-h d-flex justify-content-between gap-2"><span>Overdue payments</span><a class="small fw-normal" href="<?= e(url('invoices')) ?>">All invoices</a></div>
      <?php if (!$overdue): ?><p class="text-muted mb-0">No overdue balances.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th scope="col">Invoice</th><th scope="col">Customer</th><th scope="col">Due</th><th scope="col">Balance</th></tr></thead>
          <tbody><?php foreach ($overdue as $item): ?><tr>
            <td><a href="<?= e(url('invoices/show', ['id' => $item['invoice_id']])) ?>"><?= e($item['invoice_number']) ?></a></td>
            <td><?= e($item['first_name'] . ' ' . $item['last_name']) ?></td>
            <td class="text-nowrap"><?= e($item['due_date']) ?></td>
            <td class="text-nowrap"><?= e(\Crms\Money::format($item['outstanding_balance'] ?? 0)) ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-lg-7">
    <section class="panel table-panel h-100">
      <div class="panel-h d-flex justify-content-between gap-2"><span>Recent ledger activity</span><a class="small fw-normal" href="<?= e(url('finance')) ?>">Reconcile full ledger</a></div>
      <p class="small text-muted">Match transaction methods, references, dates, amounts and statuses against provider or bank records.</p>
      <?php if (!$recentTransactions): ?><p class="text-muted mb-0">No ledger entries have been recorded.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0">
          <thead><tr><th scope="col">Date</th><th scope="col">Reference</th><th scope="col">Type</th><th scope="col">Amount</th><th scope="col">Method</th><th scope="col">Status</th><th scope="col">Reconciliation</th><th scope="col"></th></tr></thead>
          <tbody><?php foreach ($recentTransactions as $transaction): ?><tr>
            <td class="text-nowrap"><?= e($transaction['transaction_date']) ?></td>
            <td><strong><?= e($transaction['transaction_number']) ?></strong><small class="d-block text-muted"><?= e(trim((string) $transaction['external_reference']) ?: 'No external reference') ?></small></td>
            <td class="text-nowrap"><?= e(ucwords(str_replace('_', ' ', $transaction['transaction_type']))) ?></td>
            <td class="text-nowrap"><?= e(\Crms\Money::format($transaction['amount'])) ?></td>
            <td><?= e(ucwords(str_replace('_', ' ', (string) ($transaction['payment_method'] ?: 'Not recorded')))) ?></td>
            <td><?= status_badge((string) $transaction['status']) ?></td>
            <td class="small"><?= $transaction['reconciled_at'] ? '<span class="text-success">Reconciled</span>' : ($transaction['status'] === 'completed' ? '<span class="text-muted">Needs review</span>' : '—') ?></td>
            <td><?php if ($transaction['status'] === 'completed' && in_array($transaction['transaction_type'], ['payment','deposit_received','payment_refund','deposit_refund','deposit_deduction'], true)): ?><a class="btn btn-sm btn-outline-dark" href="<?= e(url('payments/receipt', ['id' => $transaction['transaction_id']])) ?>">Receipt</a><?php endif; ?></td>
          </tr><?php endforeach; ?></tbody>
        </table></div>
      <?php endif; ?>
    </section>
  </div>
</div>
