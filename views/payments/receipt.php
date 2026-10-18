<div class="receipt-actions d-flex justify-content-between align-items-center mb-3"><a class="btn btn-outline-dark" href="<?= e(!empty($row['invoice_id']) ? url('invoices/show', ['id' => $row['invoice_id']]) : url('finance')) ?>"><i class="bi bi-arrow-left me-1"></i>Back</a><button class="btn btn-accent" type="button" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print receipt</button></div>
<article class="receipt-card card card-body mx-auto">
  <header class="receipt-header"><span class="brand-mark"><i class="bi bi-car-front-fill" aria-hidden="true"></i></span><div><strong>Rent Car Rwanda</strong><small>Kigali, Rwanda · +250 785 354 935</small></div></header>
  <?php $receiptAmountLabel = match ($row['transaction_type']) {
    'payment_refund' => 'Amount refunded',
    'deposit_refund' => 'Deposit returned',
    'deposit_deduction' => 'Deposit deduction',
    'adjustment' => 'Invoice adjustment',
    'deposit_received' => 'Deposit received',
    default => 'Amount received',
  }; ?>
  <div class="receipt-title"><p class="eyebrow">Financial record</p><h2>Receipt</h2><span><?= e($row['transaction_number']) ?></span></div>
  <div class="receipt-amount"><small><?= e($receiptAmountLabel) ?></small><strong><?= e(\Crms\Money::format($row['amount'])) ?></strong><span class="badge text-bg-success">Completed</span></div>
  <dl class="receipt-details">
    <div><dt>Customer</dt><dd><?= e($row['first_name'] . ' ' . $row['last_name']) ?></dd></div>
    <div><dt>Phone</dt><dd><?= e($row['phone']) ?></dd></div>
    <?php if ($row['invoice_number']): ?><div><dt>Invoice</dt><dd><?= e($row['invoice_number']) ?></dd></div><?php endif; ?>
    <div><dt>Transaction type</dt><dd><?= e(ucwords(str_replace('_', ' ', $row['transaction_type']))) ?></dd></div>
    <?php if ($row['transaction_type'] === 'adjustment' && $row['adjustment_direction']): ?><div><dt>Adjustment direction</dt><dd><?= e(ucfirst($row['adjustment_direction'])) ?></dd></div><?php endif; ?>
    <div><dt>Payment method</dt><dd><?= e(ucwords(str_replace('_', ' ', (string)($row['payment_method'] ?: 'Not recorded')))) ?></dd></div>
    <div><dt>Date</dt><dd><?= e($row['transaction_date']) ?></dd></div>
    <?php if ($row['external_reference']): ?><div><dt>Provider reference</dt><dd><?= e($row['external_reference']) ?></dd></div><?php endif; ?>
    <?php if ($row['reason']): ?><div><dt>Note</dt><dd><?= e($row['reason']) ?></dd></div><?php endif; ?>
  </dl>
  <footer class="receipt-footer">Thank you for choosing Rent Car Rwanda. Keep this receipt for your records.</footer>
</article>
