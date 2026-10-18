<div class="dashboard-heading">
  <div>
    <p class="eyebrow mb-1"><?= e($dashboardEyebrow ?? $role ?? 'Workspace') ?></p>
    <h1><?= e($dashboardHeading ?? ('Welcome, ' . ($user['first_name'] ?? 'there'))) ?></h1>
    <p class="text-muted mb-0"><?= e($dashboardSubtitle ?? 'Your Rent Car Rwanda workspace.') ?></p>
  </div>
  <?php if (!empty($dashboardActions)): ?>
    <div class="dashboard-heading-actions">
      <?php foreach ($dashboardActions as $i => $action): ?>
        <a class="btn <?= $i === 0 ? 'btn-primary' : 'btn-outline-dark' ?>" href="<?= e(url($action['route'], $action['query'] ?? [])) ?>"><i class="bi <?= e($action['icon'] ?? 'bi-arrow-right') ?>" aria-hidden="true"></i><?= e($action['label']) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/dashboard-duties.php'; ?>
