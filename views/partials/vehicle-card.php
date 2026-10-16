<?php
$v = $vehicle;
$quote = $quote ?? null;
$pickup = $pickup ?? '';
$returnAt = $returnAt ?? '';
$useCategoryPhoto = $useCategoryPhoto ?? false;
$categoryName = $v['category_name'] ?? 'vehicle';
$vehicleImage = $useCategoryPhoto
    ? vehicle_img(null, $categoryName)
    : vehicle_img($v['image_path'] ?? null, $categoryName);
$vehicleImageAlt = $useCategoryPhoto
    ? 'Example ' . $categoryName . ' vehicle'
    : trim(($v['make'] ?? '') . ' ' . ($v['model'] ?? ''));
?>
<article class="vehicle-card">
  <div class="vehicle-photo">
    <img class="<?= $useCategoryPhoto ? 'vehicle-local-photo' : '' ?>" src="<?= e($vehicleImage) ?>" alt="<?= e($vehicleImageAlt) ?>">
    <?= status_badge((string)($v['status'] ?? 'available')) ?>
  </div>
  <div class="vehicle-body">
    <div class="small text-muted"><?= e($v['category_name'] ?? '') ?></div>
    <h3><?= e(($v['make'] ?? '') . ' ' . ($v['model'] ?? '')) ?></h3>
    <p class="vehicle-meta"><?= e($v['registration_no'] ?? '') ?> · <?= e($v['transmission'] ?? '') ?> · <?= e($v['fuel_type'] ?? '') ?></p>
    <?php if ($quote): ?>
      <p class="vehicle-price"><?= e(\Crms\Money::format($quote['total'])) ?> <span>for <?= (int)$quote['estimated_days'] ?> day(s)</span></p>
    <?php else: ?>
      <p class="vehicle-price"><?= e(\Crms\Money::format($v['daily_rate'] ?? 0)) ?> <span>/ day</span></p>
    <?php endif; ?>
    <div class="vehicle-actions">
      <?php if (!empty($manage)): ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('vehicles/show', ['id' => $v['vehicle_id']])) ?>">View</a>
        <?php if (can('vehicles.manage')): ?>
          <a class="btn btn-sm btn-primary" href="<?= e(url('vehicles/form', ['id' => $v['vehicle_id']])) ?>">Edit in backend</a>
        <?php endif; ?>
      <?php elseif (!empty($user) && $pickup && $returnAt): ?>
        <a class="btn btn-sm btn-primary" href="<?= e(url('reservations/form', ['vehicle_id' => $v['vehicle_id'], 'pickup_datetime' => $pickup, 'return_datetime' => $returnAt])) ?>">Book this car</a>
      <?php elseif (!empty($user)): ?>
        <a class="btn btn-sm btn-primary" href="<?= e(url('search')) ?>">Check dates</a>
      <?php else: ?>
        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('login')) ?>">Sign in to book</a>
      <?php endif; ?>
    </div>
  </div>
</article>
