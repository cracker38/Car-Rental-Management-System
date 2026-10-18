<form method="post" class="panel mb-4">
  <?= \Crms\Csrf::field() ?>
  <input type="hidden" name="action" value="create">
  <div class="panel-h">Schedule maintenance</div>
  <div class="row g-2">
    <div class="col-md-3"><label class="form-label">Vehicle</label><select class="form-select" name="vehicle_id" required>
      <?php foreach ($vehicles as $v): ?><option value="<?= (int)$v['vehicle_id'] ?>"><?= e($v['registration_no']) ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-md-3"><label class="form-label">Type</label><select class="form-select" name="maintenance_type">
      <?php foreach (['routine_service','repair','inspection','tyres','oil_change','bodywork','other'] as $t): ?><option value="<?= $t ?>"><?= e(str_replace('_', ' ', $t)) ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-md-3"><label class="form-label">Title</label><input class="form-control" name="title" required placeholder="Title"></div>
    <div class="col-md-3"><label class="form-label">Start</label><input class="form-control" type="date" name="start_date" required></div>
    <div class="col-md-3"><label class="form-label">Expected completion</label><input class="form-control" type="date" name="expected_completion_date"></div>
    <div class="col-md-3"><label class="form-label">Cost (RWF)</label><input class="form-control" name="cost" value="0"></div>
    <div class="col-md-3"><label class="form-label">Odometer</label><input class="form-control" name="odometer_reading" placeholder="Odometer"></div>
    <div class="col-md-3"><label class="form-label">Next service</label><input class="form-control" type="date" name="next_service_date"></div>
    <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="scheduled">scheduled</option><option value="in_progress">in progress</option></select></div>
  </div>
  <button class="btn btn-primary mt-3">Save job</button>
</form>
<div class="panel table-panel">
  <div class="panel-h">Open and recent jobs</div>
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>No</th><th>Vehicle</th><th>Title</th><th>Dates</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="6"><div class="empty-state">No maintenance records.</div></td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= e($r['maintenance_number']) ?></td>
        <td><?= e($r['registration_no']) ?></td>
        <td><?= e($r['title']) ?></td>
        <td><?= e($r['start_date']) ?> → <?= e((string)$r['expected_completion_date']) ?></td>
        <td><?= status_badge((string)$r['status']) ?></td>
        <td>
          <?php if (in_array($r['status'], ['scheduled','in_progress'], true)): ?>
            <form method="post" class="d-inline" data-confirm="Mark complete?">
              <?= \Crms\Csrf::field() ?>
              <input type="hidden" name="action" value="complete">
              <input type="hidden" name="id" value="<?= (int)$r['maintenance_id'] ?>">
              <button class="btn btn-sm btn-outline-success">Complete</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
