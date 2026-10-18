<div class="panel table-panel">
  <div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Number</th><?php if (empty($inspectOnly)): ?><th>Customer</th><?php endif; ?><th>Vehicle</th><th><?= !empty($inspectOnly) ? 'Pickup / return' : 'Due back' ?></th><th>Status</th><?php if (can('rentals.manage') || can('rentals.view')): ?><th>Final</th><?php endif; ?><th></th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="<?= (can('rentals.manage') || can('rentals.view')) ? 7 : (!empty($inspectOnly) ? 5 : 6) ?>"><div class="empty-state"><?= !empty($inspectOnly) ? 'No rentals waiting for inspection.' : 'No rentals yet.' ?></div></td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><a href="<?= e(url('rentals/show', ['id' => $r['rental_id']])) ?>"><?= e($r['rental_number']) ?></a></td>
        <?php if (empty($inspectOnly)): ?><td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td><?php endif; ?>
        <td><?= e($r['registration_no']) ?></td>
        <td><?= e(!empty($inspectOnly) && $r['status'] === 'scheduled' ? $r['pickup_datetime'] : $r['expected_return_datetime']) ?></td>
        <td><?= status_badge((string)$r['status']) ?></td>
        <?php if (can('rentals.manage') || can('rentals.view')): ?><td><?= e(\Crms\Money::format($r['final_amount'])) ?></td><?php endif; ?>
        <td><a class="btn btn-sm btn-outline-primary" href="<?= e(url('rentals/show', ['id' => $r['rental_id']])) ?>"><?= can('inspections.manage') && !can('rentals.manage') ? 'Inspect' : 'View' ?></a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
