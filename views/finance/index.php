<?php $canManageTransactions = can('transactions.manage'); ?>
<div class="page-toolbar">
  <p class="text-muted mb-0">Completed Mobile Money rows are confirmed by HDEV. Pending rows do not change invoice balances.</p>
  <?php if ($canManageTransactions): ?><a class="btn btn-primary" href="<?= e(url('finance/form')) ?>">Post transaction</a><?php endif; ?>
</div>
<div class="panel table-panel">
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Number</th><th>Customer</th><th>Type</th><th>Amount</th><th>Method</th><th>Status</th><th>Ref</th><th>When</th><th></th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="9"><div class="empty-state">No ledger entries yet.</div></td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr <?= $canManageTransactions && $r['status'] === 'pending' && $r['external_reference'] ? 'data-payment-poll="' . e(url('payments/status', ['id' => $r['transaction_id']])) . '"' : '' ?>>
        <td><?= e($r['transaction_number']) ?></td>
        <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
        <td><?= e(str_replace('_', ' ', $r['transaction_type'])) ?></td>
        <td><?= e(\Crms\Money::format($r['amount'])) ?></td>
        <td><?= e(str_replace('_', ' ', (string)$r['payment_method'])) ?></td>
        <td><?= status_badge((string)$r['status']) ?></td>
        <td class="small"><?= e((string)$r['external_reference']) ?></td>
        <td><?= e($r['transaction_date']) ?></td>
        <td class="text-nowrap">
          <?php if ($canManageTransactions && $r['status'] === 'pending' && $r['external_reference']): ?>
            <form method="post" action="<?= e(url('payments/sync', ['id' => $r['transaction_id']])) ?>"><?= \Crms\Csrf::field() ?><button class="btn btn-sm btn-outline-secondary">Check status</button></form>
          <?php endif; ?>
          <?php if ($r['status'] === 'completed' && in_array($r['transaction_type'], ['payment','deposit_received','payment_refund','deposit_refund','deposit_deduction'], true)): ?><a class="btn btn-sm btn-outline-dark" href="<?= e(url('payments/receipt', ['id' => $r['transaction_id']])) ?>">Receipt</a><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
