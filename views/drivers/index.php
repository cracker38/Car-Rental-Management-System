<div class="page-toolbar">
  <p class="text-muted mb-0">Link each chauffeur to a Driver-role user so they can open their schedule.</p>
  <a class="btn btn-primary" href="<?= e(url('drivers/form')) ?>"><i class="bi bi-person-plus me-1"></i>Add driver</a>
</div>
<div class="panel table-panel">
  <div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Driver</th><th>Phone</th><th>Licence</th><th>Linked account</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="6"><div class="empty-state">No driver records yet.</div></td></tr><?php endif; ?>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><strong><?= e($row['first_name'] . ' ' . $row['last_name']) ?></strong><?php if ($row['email']): ?><br><small><?= e($row['email']) ?></small><?php endif; ?></td>
        <td><a href="tel:<?= e($row['phone']) ?>"><?= e($row['phone']) ?></a></td>
        <td><?= e($row['driving_license_number']) ?><?php if ($row['license_expiry_date']): ?><br><small>Expires <?= e($row['license_expiry_date']) ?></small><?php endif; ?></td>
        <td><?= e($row['account_email'] ?? 'No login linked') ?></td>
        <td><?= status_badge($row['status']) ?></td>
        <td class="text-end"><a class="btn btn-sm btn-outline-dark" href="<?= e(url('drivers/form', ['id' => $row['driver_id']])) ?>">Edit</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
