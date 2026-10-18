<?php if (!empty($kpis)): ?>
<div class="row g-3 mb-4">
  <?php foreach ($kpis as $kpi): ?>
    <div class="<?= e($kpiColClass ?? 'col-6 col-lg-3') ?>">
      <?php if (!empty($kpi['href'])): ?>
        <a class="kpi-card kpi-card-link<?= !empty($kpi['icon']) ? ' admin-metric-card' : '' ?>" href="<?= e($kpi['href']) ?>">
          <?php if (!empty($kpi['icon'])): ?><span class="admin-metric-icon"><i class="bi <?= e($kpi['icon']) ?>" aria-hidden="true"></i></span><?php endif; ?>
          <span class="admin-metric-copy">
            <span class="kpi-label"><?= e($kpi['label']) ?></span>
            <span class="kpi-value"><?= e((string)$kpi['value']) ?></span>
            <?php if (!empty($kpi['hint'])): ?><span class="kpi-hint"><?= e($kpi['hint']) ?></span><?php endif; ?>
          </span>
        </a>
      <?php else: ?>
        <div class="kpi-card<?= !empty($kpi['icon']) ? ' admin-metric-card' : '' ?>">
          <?php if (!empty($kpi['icon'])): ?><span class="admin-metric-icon"><i class="bi <?= e($kpi['icon']) ?>" aria-hidden="true"></i></span><?php endif; ?>
          <span class="admin-metric-copy">
            <span class="kpi-label"><?= e($kpi['label']) ?></span>
            <span class="kpi-value"><?= e((string)$kpi['value']) ?></span>
            <?php if (!empty($kpi['hint'])): ?><span class="kpi-hint"><?= e($kpi['hint']) ?></span><?php endif; ?>
          </span>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
