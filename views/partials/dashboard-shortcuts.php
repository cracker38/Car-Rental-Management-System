<?php if (!empty($shortcuts)): ?>
<section class="dashboard-shortcuts">
  <?php foreach ($shortcuts as $item): ?>
    <a class="shortcut-card" href="<?= e(url($item['route'])) ?>">
      <span class="shortcut-icon"><i class="bi <?= e($item['icon']) ?>" aria-hidden="true"></i></span>
      <strong><?= e($item['label']) ?></strong>
      <span><?= e($item['hint'] ?? '') ?></span>
    </a>
  <?php endforeach; ?>
</section>
<?php endif; ?>
