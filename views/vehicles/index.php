<form class="row g-2 mb-3" method="get">
  <input type="hidden" name="r" value="vehicles">
  <div class="col-md-4"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search registration, make, model"></div>
  <div class="col-md-2"><button class="btn btn-outline-primary">Filter</button></div>
  <?php if (can('vehicles.manage')): ?>
    <div class="col-md-6 text-md-end"><a class="btn btn-primary" href="<?= e(url('vehicles/form')) ?>">Add vehicle</a></div>
  <?php endif; ?>
</form>
<div class="card table-responsive">
<table class="table mb-0">
  <thead><tr><th>Reg</th><th>Vehicle</th><th>Category</th><th>Daily (RWF)</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php if (!$vehicles): ?><tr><td colspan="6" class="text-muted">No vehicles found.</td></tr><?php endif; ?>
  <?php foreach ($vehicles as $v): ?>
    <tr>
      <td><?= e($v['registration_no']) ?></td>
      <td><?= e($v['make'] . ' ' . $v['model']) ?></td>
      <td><?= e($v['category_name']) ?></td>
      <td><?= e(\Crms\Money::format($v['daily_rate'])) ?></td>
      <td><span class="badge text-bg-secondary"><?= e($v['status']) ?></span></td>
      <td>
        <a href="<?= e(url('vehicles/show', ['id' => $v['vehicle_id']])) ?>">View</a>
        <?php if (can('vehicles.manage')): ?> · <a href="<?= e(url('vehicles/form', ['id' => $v['vehicle_id']])) ?>">Edit</a><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
