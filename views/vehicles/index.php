<p class="text-muted">These records power the public Fleet and Home pages. Change a photo, rate or status here and the website updates.</p>
<form class="row g-2 mb-3" method="get">
  <input type="hidden" name="r" value="vehicles">
  <div class="col-md-4"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search registration, make, model"></div>
  <div class="col-md-2"><button class="btn btn-outline-secondary">Filter</button></div>
  <?php if (can('vehicles.manage')): ?>
    <div class="col-md-6 text-md-end"><a class="btn btn-accent" href="<?= e(url('vehicles/form')) ?>">Add vehicle</a></div>
  <?php endif; ?>
</form>
<div class="row g-4">
  <?php if (!$vehicles): ?><p class="text-muted">No vehicles found.</p><?php endif; ?>
  <?php foreach ($vehicles as $vehicle): ?>
    <div class="col-md-6 col-lg-4"><?php $manage = true; $quote = null; $pickup = ''; $returnAt = ''; require dirname(__DIR__) . '/partials/vehicle-card.php'; ?></div>
  <?php endforeach; ?>
</div>
