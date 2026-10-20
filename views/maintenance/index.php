<?php if (!empty($inspectorMode)): ?>
  <p class="lead-muted mb-3">This view is limited to vehicles currently held for maintenance. Inspectors can close service work and record a signed-off readiness decision; rental scheduling is managed by operations staff.</p>
<?php endif; ?>
<?php if (!empty($canSchedule)): ?>
<form method="post" class="panel mb-4">
  <?= \Crms\Csrf::field() ?>
  <input type="hidden" name="action" value="create">
  <div class="panel-h">Schedule servicing or maintenance</div>
  <div class="row g-2">
    <div class="col-md-3"><label class="form-label">Vehicle</label><select class="form-select" name="vehicle_id" required>
      <?php foreach ($vehicles as $v): ?><option value="<?= (int)$v['vehicle_id'] ?>"><?= e($v['registration_no']) ?><?= $v['status'] === 'maintenance' ? ' · currently unavailable' : '' ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-md-3"><label class="form-label">Service type</label><select class="form-select" name="maintenance_type" required>
      <?php foreach (['routine_service','repair','inspection','tyres','oil_change','bodywork','other'] as $t): ?><option value="<?= $t ?>"><?= e(ucwords(str_replace('_', ' ', $t))) ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-md-3"><label class="form-label">Job title</label><input class="form-control" name="title" maxlength="180" required placeholder="e.g. 40,000 km service"></div>
    <div class="col-md-3"><label class="form-label">Start date</label><input class="form-control" type="date" name="start_date" required></div>
    <div class="col-md-3"><label class="form-label">Expected completion</label><input class="form-control" type="date" name="expected_completion_date"></div>
    <div class="col-md-3"><label class="form-label">Service provider</label><input class="form-control" name="service_provider" maxlength="180"></div>
    <div class="col-md-3"><label class="form-label">Cost (RWF)</label><input class="form-control" type="number" name="cost" min="0" step="0.01" value="0" required></div>
    <div class="col-md-3"><label class="form-label">Service odometer (km)</label><input class="form-control" type="number" name="odometer_reading" min="0" step="1"></div>
    <div class="col-md-3"><label class="form-label">Next service date</label><input class="form-control" type="date" name="next_service_date"></div>
    <div class="col-12"><label class="form-label">Work required or performed</label><textarea class="form-control" name="description" rows="2" maxlength="10000" placeholder="Describe inspection findings, parts, and work to be completed"></textarea></div>
    <div class="col-12"><label class="form-label">Service notes</label><textarea class="form-control" name="notes" rows="2" maxlength="10000"></textarea></div>
  </div>
  <button class="btn btn-primary mt-3">Schedule job</button>
</form>
<?php endif; ?>

<div class="panel table-panel mb-4">
  <div class="panel-h">Vehicles currently in maintenance</div>
  <div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Vehicle</th><th>Open work</th><th>Operational status</th><?php if (!empty($canManage)): ?><th>Readiness decision</th><?php endif; ?></tr></thead>
    <tbody>
    <?php if (empty($maintenanceVehicles)): ?><tr><td colspan="<?= !empty($canManage) ? 4 : 3 ?>"><div class="empty-state">No vehicles are currently held for maintenance.</div></td></tr><?php endif; ?>
    <?php foreach ($maintenanceVehicles as $v): ?>
      <tr>
        <td><strong><?= e($v['registration_no']) ?></strong><small class="d-block text-muted"><?= e($v['make'] . ' ' . $v['model']) ?></small></td>
        <td><?= (int)$v['open_jobs'] > 0 ? (int)$v['open_jobs'] . ' open job(s)' : 'No open jobs' ?></td>
        <td><?= status_badge((string)$v['status']) ?></td>
        <?php if (!empty($canManage)): ?><td>
          <?php if ((int)$v['open_jobs'] > 0): ?>
            <span class="small text-muted">Complete or cancel all open jobs before releasing the vehicle.</span>
          <?php else: ?>
            <form method="post" class="d-flex flex-column flex-lg-row gap-2" data-confirm="Return this vehicle to operational service after inspection?">
              <?= \Crms\Csrf::field() ?>
              <input type="hidden" name="action" value="vehicle_readiness">
              <input type="hidden" name="vehicle_id" value="<?= (int)$v['vehicle_id'] ?>">
              <input type="hidden" name="vehicle_status" value="available">
              <input class="form-control form-control-sm" name="reason" maxlength="500" minlength="5" required placeholder="Inspection and release note">
              <button class="btn btn-sm btn-outline-success text-nowrap">Mark ready for service</button>
            </form>
          <?php endif; ?>
        </td><?php endif; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<div class="panel table-panel">
  <div class="panel-h">Maintenance and servicing records</div>
  <div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Job</th><th>Vehicle</th><th>Dates</th><th>Work and cost</th><th>Next service</th><th>Status</th><?php if (!empty($canManage)): ?><th>Update</th><?php endif; ?></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="<?= !empty($canManage) ? 7 : 6 ?>"><div class="empty-state">No maintenance records.</div></td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><strong><?= e($r['title']) ?></strong><small class="d-block text-muted"><?= e($r['maintenance_number']) ?> · <?= e(ucwords(str_replace('_', ' ', $r['maintenance_type']))) ?></small></td>
        <td><?= e($r['registration_no']) ?><?php if ($r['odometer_reading'] !== null): ?><small class="d-block text-muted"><?= (int)$r['odometer_reading'] ?> km</small><?php endif; ?></td>
        <td><?= e($r['start_date']) ?><?php if (!empty($r['expected_completion_date'])): ?><small class="d-block text-muted">Due <?= e($r['expected_completion_date']) ?></small><?php endif; ?><?php if (!empty($r['completed_date'])): ?><small class="d-block text-muted">Done <?= e($r['completed_date']) ?></small><?php endif; ?></td>
        <td><?= e(\Crms\Money::format($r['cost'])) ?><?php if (!empty($r['service_provider'])): ?><small class="d-block text-muted"><?= e($r['service_provider']) ?></small><?php endif; ?><?php if (!empty($r['description'])): ?><small class="d-block text-muted"><?= e($r['description']) ?></small><?php endif; ?></td>
        <td><?= e((string)($r['next_service_date'] ?? '—')) ?></td>
        <td><?= status_badge((string)$r['status']) ?></td>
        <?php if (!empty($canManage)): ?><td class="text-nowrap">
          <?php if ($r['status'] === 'scheduled'): ?>
            <form method="post" class="d-inline" data-confirm="Start this maintenance job?">
              <?= \Crms\Csrf::field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int)$r['maintenance_id'] ?>"><input type="hidden" name="new_status" value="in_progress">
              <button class="btn btn-sm btn-outline-primary">Start</button>
            </form>
          <?php elseif ($r['status'] === 'in_progress'): ?>
            <form method="post" class="mt-2 text-start" data-confirm="Complete this work and save the vehicle readiness decision?">
              <?= \Crms\Csrf::field() ?>
              <input type="hidden" name="action" value="status">
              <input type="hidden" name="id" value="<?= (int)$r['maintenance_id'] ?>">
              <input type="hidden" name="new_status" value="completed">
              <label class="form-label small">Vehicle readiness after this work</label>
              <select class="form-select form-select-sm mb-2" name="vehicle_status" required>
                <option value="">Choose readiness</option>
                <option value="available">Inspected and ready for service</option>
                <option value="maintenance">Keep in maintenance</option>
              </select>
              <label class="form-label small">Completion / inspection note</label>
              <textarea class="form-control form-control-sm mb-2" name="reason" rows="2" minlength="5" maxlength="500" required></textarea>
              <button class="btn btn-sm btn-outline-success">Complete job</button>
            </form>
          <?php endif; ?>
          <?php if (in_array($r['status'], ['scheduled','in_progress'], true)): ?>
            <form method="post" class="mt-2" data-confirm="Cancel this job only after confirming the vehicle is safe for service.">
              <?= \Crms\Csrf::field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int)$r['maintenance_id'] ?>"><input type="hidden" name="new_status" value="cancelled">
              <input class="form-control form-control-sm mb-1" name="reason" maxlength="500" placeholder="Cancellation reason" required>
              <label class="form-check small mb-1"><input class="form-check-input" type="checkbox" name="vehicle_safe" value="yes" required> Vehicle is safe to return to service</label>
              <button class="btn btn-sm btn-outline-danger">Cancel job</button>
            </form>
          <?php endif; ?>
          <details class="mt-2">
            <summary class="small">Edit service details</summary>
            <form method="post" class="mt-2 text-start">
              <?= \Crms\Csrf::field() ?><input type="hidden" name="action" value="details"><input type="hidden" name="id" value="<?= (int)$r['maintenance_id'] ?>">
              <label class="form-label small">Job title</label><input class="form-control form-control-sm mb-2" name="title" maxlength="180" value="<?= e($r['title']) ?>" required>
              <label class="form-label small">Start date</label><input class="form-control form-control-sm mb-2" type="date" name="start_date" value="<?= e($r['start_date']) ?>" required>
              <label class="form-label small">Expected completion</label><input class="form-control form-control-sm mb-2" type="date" name="expected_completion_date" value="<?= e((string)$r['expected_completion_date']) ?>">
              <label class="form-label small">Service provider</label><input class="form-control form-control-sm mb-2" name="service_provider" maxlength="180" value="<?= e((string)$r['service_provider']) ?>">
              <label class="form-label small">Cost (RWF)</label><input class="form-control form-control-sm mb-2" type="number" name="cost" min="0" step="0.01" value="<?= e((string)$r['cost']) ?>" required>
              <label class="form-label small">Service odometer (km)</label><input class="form-control form-control-sm mb-2" type="number" name="odometer_reading" min="0" step="1" value="<?= e((string)$r['odometer_reading']) ?>">
              <label class="form-label small">Next service date</label><input class="form-control form-control-sm mb-2" type="date" name="next_service_date" value="<?= e((string)$r['next_service_date']) ?>">
              <label class="form-label small">Work performed or required</label><textarea class="form-control form-control-sm mb-2" name="description" rows="3" maxlength="10000"><?= e((string)$r['description']) ?></textarea>
              <label class="form-label small">Service notes</label><textarea class="form-control form-control-sm mb-2" name="notes" rows="2" maxlength="10000"><?= e((string)$r['notes']) ?></textarea>
              <button class="btn btn-sm btn-outline-primary">Save service details</button>
            </form>
          </details>
        </td><?php endif; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
