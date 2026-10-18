<?php if ($canManage): ?>
<form class="panel mb-4" method="get">
  <input type="hidden" name="r" value="drivers/schedule">
  <div class="panel-h">Choose a driver</div>
  <div class="row g-2 align-items-end">
    <div class="col-md-8"><label class="form-label" for="schedule-driver">Driver</label>
      <select class="form-select" id="schedule-driver" name="driver_id">
        <option value="">Choose a driver</option>
        <?php foreach ($drivers as $driver): ?>
          <option value="<?= (int)$driver['driver_id'] ?>" <?= (int)$driverId === (int)$driver['driver_id'] ? 'selected' : '' ?>><?= e($driver['first_name'] . ' ' . $driver['last_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4 d-grid"><button class="btn btn-outline-dark">View schedule</button></div>
  </div>
</form>
<?php endif; ?>
<div class="panel table-panel">
  <div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Booking</th><th>Pickup</th><th>Return</th><th>Customer</th><th>Vehicle</th><th>Locations</th><th>Progress</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="7"><div class="empty-state"><?= $driverId ? 'No assigned trips found.' : 'Select a driver to view the schedule.' ?></div></td></tr><?php endif; ?>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><strong><?= e($row['reservation_code']) ?></strong><?php if ($row['assignment_notes']): ?><br><small class="text-muted"><?= e($row['assignment_notes']) ?></small><?php endif; ?></td>
        <td><?= e($row['pickup_datetime']) ?></td>
        <td><?= e($row['return_datetime']) ?></td>
        <td><?= e($row['first_name'] . ' ' . $row['last_name']) ?><br><a href="tel:<?= e($row['phone']) ?>"><?= e($row['phone']) ?></a></td>
        <td><?= e($row['make'] . ' ' . $row['model']) ?><br><small><?= e($row['registration_no']) ?></small></td>
        <td><?= e($row['pickup_location'] ?: 'Not set') ?> → <?= e($row['return_location'] ?: 'Not set') ?></td>
        <td>
          <?= status_badge((string)($row['trip_status'] ?? $row['reservation_status'])) ?>
          <?php if (!$canManage): ?>
            <?php $trip = $row; $back = 'schedule'; require dirname(__DIR__) . '/partials/driver-trip-form.php'; ?>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
