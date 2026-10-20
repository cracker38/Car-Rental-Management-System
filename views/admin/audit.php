<?php
$firstRow = $totalRows > 0 ? (($page - 1) * $perPage) + 1 : 0;
$lastRow = min($totalRows, $page * $perPage);
$windowStart = max(1, $page - 2);
$windowEnd = min($totalPages, $page + 2);
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <p class="small text-muted mb-0">Showing <?= number_format($firstRow) ?>–<?= number_format($lastRow) ?> of <?= number_format($totalRows) ?> audit events</p>
  <span class="small text-muted">50 events per page</span>
</div>
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
<?php if ($totalPages > 1): ?>
  <nav class="mt-3" aria-label="Audit log pages">
    <ul class="pagination pagination-sm flex-wrap justify-content-center">
      <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= e(url('admin/audit', ['page' => max(1, $page - 1)])) ?>" <?= $page <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Previous</a>
      </li>
      <?php if ($windowStart > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= e(url('admin/audit', ['page' => 1])) ?>">1</a></li>
        <?php if ($windowStart > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
      <?php endif; ?>
      <?php for ($pageNumber = $windowStart; $pageNumber <= $windowEnd; $pageNumber++): ?>
        <li class="page-item <?= $pageNumber === $page ? 'active' : '' ?>">
          <a class="page-link" href="<?= e(url('admin/audit', ['page' => $pageNumber])) ?>" <?= $pageNumber === $page ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a>
        </li>
      <?php endfor; ?>
      <?php if ($windowEnd < $totalPages): ?>
        <?php if ($windowEnd < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
        <li class="page-item"><a class="page-link" href="<?= e(url('admin/audit', ['page' => $totalPages])) ?>"><?= $totalPages ?></a></li>
      <?php endif; ?>
      <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= e(url('admin/audit', ['page' => min($totalPages, $page + 1)])) ?>" <?= $page >= $totalPages ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Next</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>
