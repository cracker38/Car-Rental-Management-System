<?php
$sliderMax = max((int)($maxDailyRate ?? 200000), (int)($filters['max_rate'] ?? 0));
$sliderValue = ($filters['max_rate'] ?? '') !== '' ? (int)$filters['max_rate'] : $sliderMax;
$hasDates = $pickup !== '' && $returnAt !== '';
?>
<section class="hire-page">
  <form method="get" action="<?= e(url('search')) ?>" class="hire-search-form">
    <input type="hidden" name="r" value="search">
    <header class="hire-search-hero">
      <div class="container">
        <div class="hire-search-heading">
          <p class="hire-kicker"><span></span> CAR HIRE IN RWANDA</p>
          <h1>Browse cars</h1>
          <p>Find and rent the right car for your next trip across Rwanda.</p>
        </div>
        <div class="hire-search-bar">
          <i class="bi bi-search" aria-hidden="true"></i>
          <label class="visually-hidden" for="hire-model-search">Search by make or model</label>
          <input id="hire-model-search" name="model" value="<?= e($filters['model'] ?? '') ?>" placeholder="Search by make or model..." autocomplete="off">
          <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1" aria-hidden="true"></i>Search</button>
        </div>
      </div>
    </header>

    <div class="container hire-search-content">
      <?php if ($error): ?><div class="alert alert-danger mb-4" role="alert"><?= e($error) ?></div><?php endif; ?>
      <div class="row g-4 align-items-start">
        <aside class="col-lg-3">
          <section class="hire-filter-panel" id="hire-filters" aria-labelledby="hire-filter-title">
            <div class="hire-filter-heading">
              <h2 id="hire-filter-title">Filters</h2>
              <a href="<?= e(url('search')) ?>">Clear</a>
            </div>
            <div class="hire-filter-fields">
              <fieldset class="hire-filter-group">
                <legend>Rental dates</legend>
                <label for="hire-pickup">Pick-up</label>
                <input class="form-control" id="hire-pickup" type="datetime-local" name="pickup_datetime" value="<?= e(str_replace(' ', 'T', substr((string)$pickup, 0, 16))) ?>" data-min-now>
                <label for="hire-return">Return</label>
                <input class="form-control" id="hire-return" type="datetime-local" name="return_datetime" value="<?= e(str_replace(' ', 'T', substr((string)$returnAt, 0, 16))) ?>" data-min-now>
                <small>Choose both dates to check live availability.</small>
              </fieldset>

              <fieldset class="hire-filter-group">
                <legend>Category</legend>
                <label class="visually-hidden" for="hire-category">Vehicle category</label>
                <select class="form-select" id="hire-category" name="category_id">
                  <option value="">Any category</option>
                  <?php foreach ($categories as $category): ?>
                    <option value="<?= (int)$category['category_id'] ?>" <?= (string)$categoryId === (string)$category['category_id'] ? 'selected' : '' ?>><?= e($category['category_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </fieldset>

              <fieldset class="hire-filter-group">
                <legend>Price per day</legend>
                <label class="hire-min-price-label" for="hire-min-rate">Minimum rate</label>
                <div class="input-group hire-min-price">
                  <span class="input-group-text">RWF</span>
                  <input class="form-control" id="hire-min-rate" type="number" name="min_rate" min="0" step="1000" value="<?= e($filters['min_rate'] ?? '') ?>" placeholder="No minimum">
                </div>
                <label class="hire-range-label" for="hire-max-rate">Maximum <output id="hire-max-rate-output" for="hire-max-rate" data-range-output><?= $sliderValue >= $sliderMax ? 'RWF ' . number_format($sliderValue) . '+' : 'RWF ' . number_format($sliderValue) ?></output></label>
                <input class="form-range" id="hire-max-rate" type="range" name="max_rate" min="0" max="<?= $sliderMax ?>" step="5000" value="<?= $sliderValue ?>" data-range-label>
                <div class="hire-range-scale"><span>RWF 0</span><span>RWF <?= number_format($sliderMax) ?>+</span></div>
              </fieldset>

              <fieldset class="hire-filter-group hire-filter-pair">
                <legend>Features</legend>
                <div>
                  <label for="hire-fuel">Fuel</label>
                  <select class="form-select" id="hire-fuel" name="fuel_type">
                    <option value="">Any fuel</option>
                    <?php foreach (['petrol','diesel','hybrid','electric','other'] as $fuel): ?>
                      <option value="<?= e($fuel) ?>" <?= ($filters['fuel_type'] ?? '') === $fuel ? 'selected' : '' ?>><?= e(ucfirst($fuel)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label for="hire-transmission">Transmission</label>
                  <select class="form-select" id="hire-transmission" name="transmission">
                    <option value="">Any</option>
                    <?php foreach (['automatic','manual','other'] as $transmission): ?>
                      <option value="<?= e($transmission) ?>" <?= ($filters['transmission'] ?? '') === $transmission ? 'selected' : '' ?>><?= e(ucfirst($transmission)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </fieldset>
              <input type="hidden" name="sort" value="<?= e($sort ?? 'recommended') ?>">
              <button class="btn btn-primary w-100" type="submit">Apply filters</button>
            </div>
          </section>
        </aside>

        <main class="col-lg-9" aria-labelledby="hire-results-title">
          <div class="hire-results-toolbar">
            <div>
              <p class="hire-results-eyebrow"><?= $hasDates ? 'Availability checked' : 'Ready for your trip' ?></p>
              <h2 id="hire-results-title">
                <?= count($results) ?> <?= count($results) === 1 ? 'car' : 'cars' ?> <?= $hasDates ? 'available for your dates' : 'available now' ?>
              </h2>
              <?php if ($hasDates): ?><p class="hire-results-dates"><?= e($pickup) ?> <i class="bi bi-arrow-right" aria-hidden="true"></i> <?= e($returnAt) ?></p><?php else: ?><p class="hire-results-dates">Choose rental dates to check availability for your trip.</p><?php endif; ?>
            </div>
            <div class="hire-sort-control">
              <label for="hire-sort">Sort:</label>
              <select id="hire-sort" name="sort" class="form-select" aria-label="Sort vehicles">
                <option value="recommended" <?= ($sort ?? 'recommended') === 'recommended' ? 'selected' : '' ?>>Recommended</option>
                <option value="price_low" <?= ($sort ?? '') === 'price_low' ? 'selected' : '' ?>>Price: low to high</option>
                <option value="price_high" <?= ($sort ?? '') === 'price_high' ? 'selected' : '' ?>>Price: high to low</option>
              </select>
            </div>
          </div>

          <?php if (!$results && !$error): ?>
            <div class="hire-empty-state">
              <span class="hire-empty-icon"><i class="bi bi-car-front" aria-hidden="true"></i></span>
              <h3>No cars match these filters</h3>
              <p>Try a different date range or clear one of the filters to see more vehicles.</p>
              <a class="btn btn-outline-primary" href="<?= e(url('search')) ?>">Clear filters</a>
            </div>
          <?php else: ?>
            <div class="row g-3 g-xl-4 hire-car-grid">
              <?php foreach ($results as $vehicle): ?>
                <?php
                  $quote = $quotes[(int)$vehicle['vehicle_id']] ?? null;
                  $canViewDetails = !empty($user) && (\Crms\Auth::isCustomer() || can('vehicles.view'));
                  $detailsUrl = $canViewDetails ? url('vehicles/show', ['id' => $vehicle['vehicle_id']]) : url('search', ['category_id' => $vehicle['category_id']]);
                ?>
                <div class="col-sm-6 col-xl-4">
                  <article class="hire-car-card">
                    <a class="hire-car-photo<?= $canViewDetails ? '' : ' is-static' ?>" href="<?= e($detailsUrl) ?>"<?= $canViewDetails ? '' : ' aria-label="' . e($vehicle['make'] . ' ' . $vehicle['model']) . '"' ?>>
                      <img src="<?= e(vehicle_img($vehicle['image_path'] ?? null, $vehicle['category_name'] ?? null)) ?>" alt="<?= e($vehicle['make'] . ' ' . $vehicle['model']) ?>">
                      <span class="hire-car-badge"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> <?= $hasDates ? 'Available for dates' : 'Available now' ?></span>
                    </a>
                    <div class="hire-car-body">
                      <div class="hire-car-category"><?= e($vehicle['category_name']) ?></div>
                      <h3><?= e($vehicle['make'] . ' ' . $vehicle['model']) ?><?php if (!empty($vehicle['manufacture_year'])): ?> <span><?= (int)$vehicle['manufacture_year'] ?></span><?php endif; ?></h3>
                      <div class="hire-car-specs">
                        <span><i class="bi bi-gear" aria-hidden="true"></i><?= e(ucfirst($vehicle['transmission'])) ?></span>
                        <span><i class="bi bi-fuel-pump" aria-hidden="true"></i><?= e(ucfirst($vehicle['fuel_type'])) ?></span>
                      </div>
                      <div class="hire-car-price">
                        <?php if ($quote): ?>
                          <strong><?= e(\Crms\Money::format($quote['total'])) ?></strong><span>total for <?= (int)$quote['estimated_days'] ?> day<?= (int)$quote['estimated_days'] === 1 ? '' : 's' ?></span>
                        <?php else: ?>
                          <strong><?= e(\Crms\Money::format($vehicle['daily_rate'])) ?></strong><span>per day</span>
                        <?php endif; ?>
                      </div>
                      <div class="hire-car-actions">
                        <?php if (!empty($user) && $hasDates): ?>
                          <a class="btn btn-primary" href="<?= e(url('reservations/form', ['vehicle_id' => $vehicle['vehicle_id'], 'pickup_datetime' => str_replace(' ', 'T', substr((string)$pickup, 0, 16)), 'return_datetime' => str_replace(' ', 'T', substr((string)$returnAt, 0, 16))])) ?>">Rent this car</a>
                        <?php elseif (!empty($user)): ?>
                          <a class="btn btn-primary" href="#hire-pickup">Choose dates</a>
                        <?php else: ?>
                          <a class="btn btn-primary" href="<?= e(url('login')) ?>">Sign in to rent</a>
                        <?php endif; ?>
                        <?php if ($canViewDetails): ?><a class="hire-details-link" href="<?= e($detailsUrl) ?>" aria-label="View details for <?= e($vehicle['make'] . ' ' . $vehicle['model']) ?>">Details <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a><?php endif; ?>
                      </div>
                    </div>
                  </article>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </main>
      </div>
    </div>
  </form>
</section>
