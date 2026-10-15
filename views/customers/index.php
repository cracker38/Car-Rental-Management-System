<form class="row g-2 mb-3">
  <input type="hidden" name="r" value="customers">
  <div class="col-md-4"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search customers"></div>
  <div class="col-md-2"><button class="btn btn-outline-primary">Filter</button></div>
  <?php if (can('customers.manage')): ?><div class="col-md-6 text-md-end"><a class="btn btn-primary" href="<?= e(url('customers/form')) ?>">Add customer</a></div><?php endif; ?>
</form>
<div class="card table-responsive"><table class="table mb-0">
  <thead><tr><th>Code</th><th>Name</th><th>Phone</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($customers as $c): ?>
    <tr>
      <td><?= e($c['customer_code']) ?></td>
      <td><?= e($c['first_name'] . ' ' . $c['last_name']) ?></td>
      <td><?= e($c['phone']) ?></td>
      <td><?= e($c['status']) ?></td>
      <td><?php if (can('customers.manage')): ?><a href="<?= e(url('customers/form', ['id' => $c['customer_id']])) ?>">Edit</a><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
