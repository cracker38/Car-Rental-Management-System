<section class="panel mb-4">
  <div class="panel-h">Database backup</div>
  <p class="text-muted">Create a SQL export of the current database. Restore is a manual phpMyAdmin or mysql import.</p>
  <form method="post">
    <?= \Crms\Csrf::field() ?>
    <input type="hidden" name="action" value="export">
    <button class="btn btn-primary">Create SQL backup</button>
  </form>
</section>
<section class="panel">
  <div class="panel-h">Recent exports</div>
  <?php if (!$files): ?><p class="text-muted mb-0">No backups have been created yet.</p>
  <?php else: ?><ul class="list-unstyled mb-0"><?php foreach ($files as $f): ?>
    <li class="mini-row"><strong><?= e(basename($f)) ?></strong><span><?= e(number_format((int)filesize($f))) ?> bytes</span></li>
  <?php endforeach; ?></ul><?php endif; ?>
</section>
