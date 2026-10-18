<?php if (!empty($duties) || !empty($restriction)): ?>
<div class="row g-3 mb-3">
  <?php if (!empty($duties)): ?>
  <div class="<?= !empty($restriction) ? 'col-lg-8' : 'col-12' ?>">
    <section class="panel h-100">
      <div class="panel-h"><?= e($dashboardDutiesHeading ?? 'What you can do here') ?></div>
      <ul class="duties-list mb-0">
        <?php foreach ($duties as $duty): ?><li><?= e($duty) ?></li><?php endforeach; ?>
      </ul>
    </section>
  </div>
  <?php endif; ?>
  <?php if (!empty($restriction)): ?>
  <div class="<?= !empty($duties) ? 'col-lg-4' : 'col-12' ?>">
    <section class="panel h-100">
      <div class="panel-h"><?= e($dashboardRestrictionHeading ?? 'Limits') ?></div>
      <p class="mb-0"><?= e($restriction) ?></p>
    </section>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>
