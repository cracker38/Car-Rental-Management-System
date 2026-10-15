<?php if (can('reservations.manage')): ?>
  <p><a class="btn btn-primary" href="<?= e(url('reservations/form')) ?>">New reservation</a></p>
<?php endif; ?>
<div class="card table-responsive"><table class="table mb-0">
  <thead><tr><th>Code</th><th>Customer</th><th>Vehicle</th><th>Pickup</th><th>Return</th><th>Est.</th><th>Status</th></tr></thead>
  <tbody>
  <?php if (!$rows): ?><tr><td colspan="7" class="text-muted">No reservations.</td></tr><?php endif; ?>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><a href="<?= e(url('reservations/show', ['id' => $r['reservation_id']])) ?>"><?= e($r['reservation_code']) ?></a></td>
      <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
      <td><?= e($r['registration_no']) ?></td>
      <td><?= e($r['pickup_datetime']) ?></td>
      <td><?= e($r['return_datetime']) ?></td>
      <td><?= e(\Crms\Money::format($r['estimated_amount'])) ?></td>
      <td><span class="badge text-bg-secondary"><?= e($r['status']) ?></span></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
