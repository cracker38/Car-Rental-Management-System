<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-muted">Active fleet</div><div class="h4"><?= (int)$stats['vehicles'] ?></div></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-muted">Open reservations</div><div class="h4"><?= (int)$stats['reservations'] ?></div></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-muted">Live rentals</div><div class="h4"><?= (int)$stats['active_rentals'] ?></div></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-muted">Invoice outstanding</div><div class="h4"><?= e(\Crms\Money::format($stats['outstanding'])) ?></div></div></div></div>
</div>
<?php if ($mine): ?>
  <h2 class="h5">My reservations</h2>
  <div class="table-responsive card mb-4"><table class="table mb-0">
    <thead><tr><th>Code</th><th>Pickup</th><th>Return</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($mine as $r): ?>
      <tr>
        <td><a href="<?= e(url('reservations/show', ['id' => $r['reservation_id']])) ?>"><?= e($r['reservation_code']) ?></a></td>
        <td><?= e($r['pickup_datetime']) ?></td>
        <td><?= e($r['return_datetime']) ?></td>
        <td><span class="badge text-bg-secondary"><?= e($r['status']) ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
<?php endif; ?>
<h2 class="h5">Notifications</h2>
<?php if (!$notes): ?><p class="text-muted">No notifications yet.</p><?php endif; ?>
<ul class="list-group">
  <?php foreach ($notes as $n): ?>
    <li class="list-group-item"><strong><?= e($n['subject']) ?></strong><div class="small"><?= e($n['message']) ?></div></li>
  <?php endforeach; ?>
</ul>
