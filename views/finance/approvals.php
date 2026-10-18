<div class="page-toolbar">
  <p class="text-muted mb-0">Reviewers cannot approve their own requests. Approval posts the transaction to the financial ledger; rejection leaves it unposted.</p>
  <a class="btn btn-outline-dark" href="<?= e(url('dashboard')) ?>">Business dashboard</a>
</div>
<section class="panel table-panel">
  <div class="panel-h">Requests awaiting independent review</div>
  <?php if (!$rows): ?><div class="empty-state">No financial requests are awaiting approval.</div><?php else: ?>
    <div class="table-responsive"><table class="table align-middle mb-0">
      <thead><tr><th>Requested</th><th>Customer / requester</th><th>Request</th><th>Amount</th><th>Method / reference</th><th>Reason</th><th>Decision</th></tr></thead>
      <tbody><?php foreach ($rows as $row): ?>
        <tr>
          <td class="text-nowrap"><?= e($row['created_at']) ?><small class="d-block text-muted"><?= e($row['transaction_number']) ?></small></td>
          <td><?= e($row['first_name'] . ' ' . $row['last_name']) ?><small class="d-block text-muted">Requested by <?= e(trim(($row['requester_first_name'] ?? '') . ' ' . ($row['requester_last_name'] ?? '')) ?: 'Unavailable user') ?></small></td>
          <td><?= e(ucwords(str_replace('_', ' ', $row['transaction_type']))) ?><?php if ($row['invoice_number']): ?><small class="d-block text-muted">Invoice <?= e($row['invoice_number']) ?></small><?php endif; ?></td>
          <td class="text-nowrap"><?= e(\Crms\Money::format($row['amount'])) ?><?php if ($row['adjustment_direction']): ?><small class="d-block text-muted"><?= e(ucfirst($row['adjustment_direction'])) ?></small><?php endif; ?></td>
          <td><?= e(ucwords(str_replace('_', ' ', (string) ($row['payment_method'] ?: 'Not recorded')))) ?><small class="d-block text-muted"><?= e($row['external_reference'] ?: 'No reference') ?></small></td>
          <td class="small"><?= e($row['reason'] ?: '—') ?></td>
          <td class="min-w-approval">
            <form method="post" action="<?= e(url('finance/review')) ?>" class="d-grid gap-2">
              <?= \Crms\Csrf::field() ?>
              <input type="hidden" name="transaction_id" value="<?= (int) $row['transaction_id'] ?>">
              <label class="visually-hidden" for="review-note-<?= (int) $row['transaction_id'] ?>">Review note (required when rejecting)</label>
              <textarea class="form-control form-control-sm" id="review-note-<?= (int) $row['transaction_id'] ?>" name="approval_note" rows="2" placeholder="Review note; required to reject"></textarea>
              <div class="d-flex gap-2">
                <button class="btn btn-sm btn-primary" name="decision" value="approve">Approve</button>
                <button class="btn btn-sm btn-outline-danger" name="decision" value="reject">Reject</button>
              </div>
            </form>
          </td>
        </tr>
      <?php endforeach; ?></tbody>
    </table></div>
  <?php endif; ?>
</section>
