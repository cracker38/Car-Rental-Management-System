<form method="post" class="mb-3">
  <?= \Crms\Csrf::field() ?>
  <input type="hidden" name="action" value="export">
  <button class="btn btn-primary">Create SQL backup</button>
</form>
<p class="text-muted">Export is implemented. Restore is a manual phpMyAdmin/mysql import until a dedicated restore test is run and documented.</p>
<ul>
  <?php foreach ($files as $f): ?>
    <li><?= e(basename($f)) ?> · <?= e((string)filesize($f)) ?> bytes</li>
  <?php endforeach; ?>
</ul>
