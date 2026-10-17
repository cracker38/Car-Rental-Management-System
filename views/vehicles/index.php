<form class="page-toolbar" method="get">
  <input type="hidden" name="r" value="vehicles">
  <p class="text-muted mb-0 me-auto">These records power the public fleet. Change a photo, rate or status here and the website updates.</p>
  <input class="form-control" style="max-width:280px" name="q" value="<?= e($q) ?>" placeholder="Search registration, make, model">
  <button class="btn btn-outline-dark">Filter</button>
  <?php if (can('vehicles.manage')): ?>
    <a class="btn btn-primary" href="<?= e(url('vehicles/form')) ?>">Add vehicle</a>
  <?php endif; ?>
</form>
<div class="row g-4">
  <?php if (!$vehicles): ?><p class="text-muted">No vehicles found.</p><?php endif; ?>
  <?php foreach ($vehicles as $vehicle): ?>
    <div class="col-md-6 col-lg-4"><?php $manage = true; $quote = null; $pickup = ''; $returnAt = ''; require dirname(__DIR__) . '/partials/vehicle-card.php'; ?></div>
  <?php endforeach; ?>
</div>
