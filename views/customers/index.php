<form class="page-toolbar" method="get">
  <input type="hidden" name="r" value="customers">
  <input class="form-control" style="max-width:280px" name="q" value="<?= e($q) ?>" placeholder="Search customers">
  <button class="btn btn-outline-dark">Filter</button>
  <?php if (can('customers.manage')): ?><a class="btn btn-primary ms-auto" href="<?= e(url('customers/form')) ?>">Add customer</a><?php endif; ?>
</form>
<div class="panel table-panel">
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Code</th><th>Name</th><th>Phone</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php if (!$customers): ?><tr><td colspan="5"><div class="empty-state">No customers found.</div></td></tr><?php endif; ?>
      <?php foreach ($customers as $c): ?>
        <tr>
          <td><?= e($c['customer_code']) ?></td>
          <td><?= e($c['first_name'] . ' ' . $c['last_name']) ?></td>
          <td><?= e($c['phone']) ?></td>
          <td><?= status_badge((string)$c['status']) ?></td>
          <td><?php if (can('customers.manage')): ?><a class="btn btn-sm btn-outline-dark" href="<?= e(url('customers/form', ['id' => $c['customer_id']])) ?>">Edit</a><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
