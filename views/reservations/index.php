<div class="page-toolbar">
  <p class="text-muted mb-0">Bookings, confirmations, and customer trips.</p>
  <?php if (can('reservations.manage')): ?>
    <a class="btn btn-primary" href="<?= e(url('reservations/form')) ?>">New reservation</a>
  <?php endif; ?>
</div>
<?php $isCustomer = \Crms\Auth::isCustomer(); ?>
<div class="panel table-panel">
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Code</th><?php if (!$isCustomer): ?><th>Customer</th><?php endif; ?><th>Vehicle</th><th>Pickup</th><th>Return</th><th>Est.</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="<?= $isCustomer ? '7' : '8' ?>"><div class="empty-state">No reservations yet.</div></td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><a href="<?= e(url('reservations/show', ['id' => $r['reservation_id']])) ?>"><?= e($r['reservation_code']) ?></a></td>
        <?php if (!$isCustomer): ?><td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td><?php endif; ?>
        <td><?= e($r['registration_no']) ?></td>
        <td><?= e($r['pickup_datetime']) ?></td>
        <td><?= e($r['return_datetime']) ?></td>
        <td><?= e(\Crms\Money::format($r['estimated_amount'])) ?></td>
        <td><?= status_badge(\Crms\Ui::reservationStatusLabel((string) $r['status'], (string) ($r['payment_status'] ?? 'unpaid'))) ?></td>
        <td><?php if (($r['status'] === 'pending' && (can('reservations.manage') || \Crms\Auth::isCustomer())) || (can('reservations.manage') && $r['status'] === 'confirmed' && $r['pickup_datetime'] > date('Y-m-d H:i:s') && empty($r['has_rental']))): ?><a class="btn btn-sm btn-outline-primary" href="<?= e(url('reservations/form', ['id' => $r['reservation_id']])) ?>"><?= \Crms\Auth::isCustomer() && $r['status'] === 'pending' && ((int) ($r['customer_signature_user_id'] ?? 0) !== (int) ($r['customer_user_id'] ?? 0) || empty($r['customer_signed_at'])) ? 'Review &amp; sign' : 'Edit' ?></a><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
