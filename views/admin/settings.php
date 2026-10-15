<form method="post" class="card card-body">
  <?= \Crms\Csrf::field() ?>
  <?php foreach ($rows as $r): ?>
    <div class="mb-3">
      <label class="form-label"><?= e($r['setting_key']) ?></label>
      <?php if ((int)$r['is_sensitive'] === 1): ?>
        <input class="form-control" name="<?= e($r['setting_key']) ?>" value="" placeholder="Stored (hidden)">
      <?php else: ?>
        <input class="form-control" name="<?= e($r['setting_key']) ?>" value="<?= e((string)$r['setting_value']) ?>">
      <?php endif; ?>
      <div class="form-text"><?= e((string)$r['description']) ?></div>
    </div>
  <?php endforeach; ?>
  <button class="btn btn-primary">Save settings</button>
</form>
