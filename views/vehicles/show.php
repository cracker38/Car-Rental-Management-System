<div class="card card-body mb-3">
  <h2 class="h5"><?= e($vehicle['make'] . ' ' . $vehicle['model']) ?> · <?= e($vehicle['registration_no']) ?></h2>
  <p>Status: <span class="badge text-bg-secondary"><?= e($vehicle['status']) ?></span> · Daily <?= e(\Crms\Money::format($vehicle['daily_rate'])) ?></p>
  <p class="mb-0">Odometer <?= (int)$vehicle['current_mileage'] ?> km · <?= e($vehicle['category_name']) ?></p>
</div>
<h3 class="h6">Recent reservations</h3>
<div class="table-responsive card"><table class="table mb-0">
  <thead><tr><th>Code</th><th>Pickup</th><th>Return</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($history as $h): ?>
    <tr><td><?= e($h['reservation_code']) ?></td><td><?= e($h['pickup_datetime']) ?></td><td><?= e($h['return_datetime']) ?></td><td><?= e($h['status']) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
