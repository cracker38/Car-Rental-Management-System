<?php
$tripStatus = (string) ($trip['trip_status'] ?? 'assigned');
$back = $back ?? 'dashboard';
?>
<form method="post" action="<?= e(url('drivers/trip')) ?>" class="driver-trip-form mt-2">
  <?= \Crms\Csrf::field() ?>
  <input type="hidden" name="assignment_id" value="<?= (int) $trip['assignment_id'] ?>">
  <input type="hidden" name="back" value="<?= e($back) ?>">
  <div class="row g-2 align-items-end">
    <div class="col-sm-4">
      <label class="form-label">Trip progress</label>
      <select class="form-select" name="trip_status" required>
        <?php foreach (['assigned' => 'Assigned', 'en_route' => 'En route', 'on_site' => 'On site', 'completed' => 'Completed', 'delayed' => 'Delayed'] as $value => $label): ?>
          <option value="<?= e($value) ?>" <?= $tripStatus === $value ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-sm-6">
      <label class="form-label">Delay or incident</label>
      <input class="form-control" name="incident_report" maxlength="500" placeholder="Optional note" value="<?= e((string) ($trip['incident_report'] ?? '')) ?>">
    </div>
    <div class="col-sm-2 d-grid">
      <button class="btn btn-outline-dark">Update</button>
    </div>
  </div>
</form>
