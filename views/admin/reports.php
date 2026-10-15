<?php if ($fin): ?>
  <div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card card-body">Completed payments<br><strong><?= e(\Crms\Money::format($fin['payments'])) ?></strong></div></div>
    <div class="col-md-4"><div class="card card-body">Refunds<br><strong><?= e(\Crms\Money::format($fin['refunds'])) ?></strong></div></div>
    <div class="col-md-4"><div class="card card-body">Deposits received<br><strong><?= e(\Crms\Money::format($fin['deposits'])) ?></strong></div></div>
  </div>
<?php endif; ?>
<div class="card table-responsive"><table class="table mb-0">
  <thead><tr><th>Vehicle</th><th>Status</th><th>Confirmed bookings</th><th>Live rentals</th></tr></thead>
  <tbody>
  <?php foreach ($ops as $o): ?>
    <tr>
      <td><?= e($o['registration_no']) ?></td>
      <td><?= e($o['status']) ?></td>
      <td><?= (int)$o['confirmed_bookings'] ?></td>
      <td><?= (int)$o['live_rentals'] ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
