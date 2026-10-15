<div class="card table-responsive"><table class="table mb-0">
  <thead><tr><th>Number</th><th>Customer</th><th>Vehicle</th><th>Due back</th><th>Status</th><th>Final</th></tr></thead>
  <tbody>
  <?php if (!$rows): ?><tr><td colspan="6" class="text-muted">No rentals.</td></tr><?php endif; ?>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><a href="<?= e(url('rentals/show', ['id' => $r['rental_id']])) ?>"><?= e($r['rental_number']) ?></a></td>
      <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
      <td><?= e($r['registration_no']) ?></td>
      <td><?= e($r['expected_return_datetime']) ?></td>
      <td><?= e($r['status']) ?></td>
      <td><?= e(\Crms\Money::format($r['final_amount'])) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
