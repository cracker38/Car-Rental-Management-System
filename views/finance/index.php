<?php $canManageTransactions = can('transactions.manage'); ?>
<div class="page-toolbar">
  <p class="text-muted mb-0">Mobile Money payments wait for provider confirmation. Refunds and adjustments need independent approval; reconcile completed entries against their bank or receipt records.</p>
  <?php if (can('transactions.approve')): ?><a class="btn btn-outline-dark" href="<?= e(url('finance/approvals')) ?>">Review finance approvals</a><?php endif; ?>
  <?php if ($canManageTransactions): ?><a class="btn btn-primary" href="<?= e(url('finance/form')) ?>">Post transaction</a><?php endif; ?>
</div>
<div class="panel table-panel">
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Number</th><th>Customer</th><th>Type</th><th>Amount</th><th>Method</th><th>Status</th><th>Reconciliation</th><th>Ref</th><th>When</th><th></th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="10"><div class="empty-state">No ledger entries yet.</div></td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr <?= $canManageTransactions && $r['status'] === 'pending' && $r['external_reference'] ? 'data-payment-poll="' . e(url('payments/status', ['id' => $r['transaction_id']])) . '"' : '' ?>>
        <td><?= e($r['transaction_number']) ?></td>
        <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
        <td><?= e(str_replace('_', ' ', $r['transaction_type'])) ?></td>
        <td><?= e(\Crms\Money::format($r['amount'])) ?></td>
        <td><?= e(str_replace('_', ' ', (string)$r['payment_method'])) ?></td>
        <td><?= status_badge((string)$r['status']) ?><?php if ($r['status'] === 'rejected' && !empty($r['approval_note'])): ?><small class="d-block text-muted">Rejected<?= $r['approval_reviewer_first_name'] ? ' by ' . e($r['approval_reviewer_first_name'] . ' ' . $r['approval_reviewer_last_name']) : '' ?>: <?= e(substr((string) $r['approval_note'], 0, 80)) ?></small><?php elseif ($r['status'] === 'awaiting_approval'): ?><small class="d-block text-muted">Awaiting reviewer</small><?php elseif ($r['approval_reviewed_at']): ?><small class="d-block text-muted">Approved<?= $r['approval_reviewer_first_name'] ? ' by ' . e($r['approval_reviewer_first_name'] . ' ' . $r['approval_reviewer_last_name']) : '' ?> &middot; <?= e($r['approval_reviewed_at']) ?></small><?php endif; ?></td>
        <td class="small">
          <?php if ($r['reconciled_at']): ?>
            <span class="text-success">Reconciled</span><small class="d-block text-muted"><?= e($r['reconciled_at']) ?><?php if ($r['reconciler_first_name']): ?> &middot; <?= e($r['reconciler_first_name'] . ' ' . $r['reconciler_last_name']) ?><?php endif; ?></small><small class="d-block"><?= e($r['reconciliation_note']) ?></small>
          <?php elseif ($canManageTransactions && $r['status'] === 'completed'): ?>
            <form method="post" action="<?= e(url('finance/reconcile')) ?>" class="d-flex gap-1">
              <?= \Crms\Csrf::field() ?><input type="hidden" name="transaction_id" value="<?= (int) $r['transaction_id'] ?>">
              <input class="form-control form-control-sm" name="reconciliation_note" required maxlength="500" placeholder="Statement / receipt ref" aria-label="Reconciliation reference for <?= e($r['transaction_number']) ?>">
              <button class="btn btn-sm btn-outline-primary">Reconcile</button>
            </form>
          <?php else: ?>—<?php endif; ?>
        </td>
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
