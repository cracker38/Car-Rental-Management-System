<div class="row g-3 mb-4">
  <?php foreach ($kpis as $kpi): ?>
    <div class="col-6 col-lg-3">
      <div class="kpi-card">
        <div class="kpi-label"><?= e($kpi['label']) ?></div>
        <div class="kpi-value"><?= e((string)$kpi['value']) ?></div>
        <?php if (!empty($kpi['hint'])): ?><div class="kpi-hint"><?= e($kpi['hint']) ?></div><?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
