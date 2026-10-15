<section class="section search-page">
  <div class="container">
    <div class="search-page-heading"><p class="eyebrow">Find the right vehicle</p><h1 class="page-title">Hire a car</h1><p class="intro">Choose your dates and filters. Availability is checked against confirmed bookings, active rentals, and maintenance.</p></div>
    <form class="booking-panel search-filter-panel" method="get" action="<?= e(url('search')) ?>">
      <input type="hidden" name="r" value="search">
      <div class="row g-3 align-items-end">
        <div class="col-md-6 col-xl-3"><label class="form-label" for="search-pickup">Pick-up date &amp; time</label><input class="form-control" id="search-pickup" type="datetime-local" name="pickup_datetime" value="<?= e(str_replace(' ', 'T', substr((string)$pickup, 0, 16))) ?>" required data-min-now></div>
        <div class="col-md-6 col-xl-3"><label class="form-label" for="search-return">Return date &amp; time</label><input class="form-control" id="search-return" type="datetime-local" name="return_datetime" value="<?= e(str_replace(' ', 'T', substr((string)$returnAt, 0, 16))) ?>" required data-min-now></div>
        <div class="col-md-6 col-xl-3"><label class="form-label" for="search-category">Category</label><select class="form-select" id="search-category" name="category_id"><option value="">Any category</option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['category_id'] ?>" <?= (string)$categoryId === (string)$c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6 col-xl-3"><label class="form-label" for="search-model">Make or model</label><input class="form-control" id="search-model" name="model" value="<?= e($filters['model'] ?? '') ?>" placeholder="e.g. Toyota or RAV4"></div>
        <div class="col-sm-6 col-lg-3"><label class="form-label" for="search-min-rate">Minimum daily rate (RWF)</label><input class="form-control" id="search-min-rate" type="number" min="0" step="1000" name="min_rate" value="<?= e($filters['min_rate'] ?? '') ?>" placeholder="No minimum"></div>
        <div class="col-sm-6 col-lg-3"><label class="form-label" for="search-max-rate">Maximum daily rate (RWF)</label><input class="form-control" id="search-max-rate" type="number" min="0" step="1000" name="max_rate" value="<?= e($filters['max_rate'] ?? '') ?>" placeholder="No maximum"></div>
        <div class="col-sm-6 col-lg-2"><label class="form-label" for="search-fuel">Fuel type</label><select class="form-select" id="search-fuel" name="fuel_type"><option value="">Any fuel</option><?php foreach (['petrol','diesel','hybrid','electric','other'] as $fuel): ?><option value="<?= e($fuel) ?>" <?= ($filters['fuel_type'] ?? '') === $fuel ? 'selected' : '' ?>><?= e(ucfirst($fuel)) ?></option><?php endforeach; ?></select></div>
        <div class="col-sm-6 col-lg-2"><label class="form-label" for="search-transmission">Transmission</label><select class="form-select" id="search-transmission" name="transmission"><option value="">Any</option><?php foreach (['automatic','manual','other'] as $transmission): ?><option value="<?= e($transmission) ?>" <?= ($filters['transmission'] ?? '') === $transmission ? 'selected' : '' ?>><?= e(ucfirst($transmission)) ?></option><?php endforeach; ?></select></div>
        <div class="col-lg-2 d-grid"><button class="btn btn-accent" type="submit">Search cars <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></button></div>
      </div>
      <div class="search-filter-footer"><span><i class="bi bi-shield-check" aria-hidden="true"></i> Final pricing is calculated securely on the server.</span><a href="<?= e(url('search')) ?>">Clear filters</a></div>
    </form>
    <?php if ($error): ?><div class="alert alert-danger mt-4" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?php if ($pickup && $returnAt && !$error): ?>
      <div class="search-results-heading"><div><p class="eyebrow">Your dates</p><h2><?= count($results) ?> <?= count($results) === 1 ? 'vehicle' : 'vehicles' ?> available</h2></div><span><?= e($pickup) ?> <i class="bi bi-arrow-right" aria-hidden="true"></i> <?= e($returnAt) ?></span></div>
      <?php if (!$results): ?><div class="fleet-empty"><i class="bi bi-calendar-x" aria-hidden="true"></i><h3>No vehicles match those dates</h3><p>Try a different period or adjust your filters. Our Kigali team can also help find an option.</p><a class="btn btn-outline-dark" href="<?= e(url('contact')) ?>">Contact us</a></div><?php endif; ?>
      <div class="row g-4">
        <?php foreach ($results as $vehicle): $quote = $quotes[(int)$vehicle['vehicle_id']] ?? null; $manage = false; require dirname(__DIR__) . '/partials/vehicle-card.php'; ?>
      <?php endforeach; ?>
      </div>
    <?php elseif (!$error): ?>
      <div class="search-prompt"><i class="bi bi-calendar2-range" aria-hidden="true"></i><div><strong>Start with your travel dates</strong><p>Choose pick-up and return times to check the live fleet.</p></div></div>
    <?php endif; ?>
  </div>
</section>
