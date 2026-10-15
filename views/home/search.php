<section class="section">
  <div class="container">
    <h1 class="page-title">Book a car</h1>
    <p class="intro">A search is not a lock. When you submit a reservation, the system rechecks the calendar so two people cannot take the same car for overlapping times.</p>
    <form class="booking-panel row g-2 align-items-end mb-4" method="get">
      <input type="hidden" name="r" value="search">
      <div class="col-md-4"><label class="form-label">Pickup</label><input class="form-control" type="datetime-local" name="pickup_datetime" value="<?= e(str_replace(' ', 'T', substr((string)$pickup, 0, 16))) ?>" required></div>
      <div class="col-md-4"><label class="form-label">Return</label><input class="form-control" type="datetime-local" name="return_datetime" value="<?= e(str_replace(' ', 'T', substr((string)$returnAt, 0, 16))) ?>" required></div>
      <div class="col-md-2"><label class="form-label">Category</label>
        <select class="form-select" name="category_id">
          <option value="">Any</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['category_id'] ?>" <?= (string)$categoryId === (string)$c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><button class="btn btn-accent w-100">Search</button></div>
    </form>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <?php if ($pickup && $returnAt && !$error): ?>
      <?php if (!$results): ?><div class="alert alert-info">No vehicles are free for those dates. Try another period or category.</div><?php endif; ?>
      <div class="row g-4">
        <?php foreach ($results as $vehicle): $quote = $quotes[(int)$vehicle['vehicle_id']] ?? null; ?>
          <div class="col-md-6 col-lg-4"><?php require dirname(__DIR__) . '/partials/vehicle-card.php'; ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
