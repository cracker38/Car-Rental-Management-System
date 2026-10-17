<div class="panel table-panel">
  <div class="table-responsive"><table class="table table-sm mb-0">
    <thead><tr><th>When</th><th>User</th><th>Action</th><th>Entity</th><th>Result</th><th>Description</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="6"><div class="empty-state">No audit events yet.</div></td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= e($r['created_at']) ?></td>
        <td><?= e((string)$r['email']) ?></td>
        <td><?= e($r['action']) ?></td>
        <td><?= e($r['entity_type'] . ' ' . (string)$r['entity_id']) ?></td>
        <td><?= status_badge((string)$r['result']) ?></td>
        <td><?= e((string)$r['description']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
