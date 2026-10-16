<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
  <div>
    <p class="eyebrow mb-1">Dashboard</p>
    <h1 class="h3 mb-1">Welcome, <?= e($user['first_name'] ?? 'there') ?></h1>
    <p class="text-muted mb-0"><?= e($dashboardSubtitle ?? ($role ?? 'Workspace')) ?></p>
  </div>
  <?php if (!empty($dashboardActions)): ?>
    <div class="d-flex flex-wrap gap-2">
      <?php foreach ($dashboardActions as $action): ?>
        <a class="btn btn-outline-dark" href="<?= e(url($action['route'])) ?>"><i class="bi <?= e($action['icon'] ?? 'bi-arrow-right') ?> me-1" aria-hidden="true"></i><?= e($action['label']) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
