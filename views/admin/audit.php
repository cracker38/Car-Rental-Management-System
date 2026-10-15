<div class="card table-responsive"><table class="table table-sm mb-0">
  <thead><tr><th>When</th><th>User</th><th>Action</th><th>Entity</th><th>Result</th><th>Description</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['created_at']) ?></td>
      <td><?= e((string)$r['email']) ?></td>
      <td><?= e($r['action']) ?></td>
      <td><?= e($r['entity_type'] . ' ' . (string)$r['entity_id']) ?></td>
      <td><?= e($r['result']) ?></td>
      <td><?= e((string)$r['description']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
