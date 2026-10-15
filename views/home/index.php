<section class="hero mb-4">
  <h1 class="display-6">Professional car hire for Rwanda</h1>
  <p class="lead mb-4">Search by date and time. Availability is checked against confirmed bookings, active rentals and maintenance — not vehicle status alone.</p>
  <form class="row g-2" method="get" action="<?= e(url('search')) ?>">
    <input type="hidden" name="r" value="search">
    <div class="col-md-4"><label class="form-label">Pickup</label><input class="form-control" type="datetime-local" name="pickup_datetime" required data-min-now></div>
    <div class="col-md-4"><label class="form-label">Return</label><input class="form-control" type="datetime-local" name="return_datetime" required></div>
    <div class="col-md-3"><label class="form-label">Category</label>
      <select class="form-select" name="category_id">
        <option value="">Any</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int)$c['category_id'] ?>"><?= e($c['category_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-1 d-flex align-items-end"><button class="btn btn-light w-100">Go</button></div>
  </form>
</section>
<h2 class="h5 mb-3">Fleet snapshot</h2>
<div class="row g-3">
  <?php if (!$vehicles): ?><p class="text-muted">No vehicles yet. Run install.php or add vehicles after signing in.</p><?php endif; ?>
  <?php foreach ($vehicles as $v): ?>
    <div class="col-md-3">
      <div class="card h-100">
        <?php if (!empty($v['image_path'])): ?><img src="<?= e(asset($v['image_path'])) ?>" class="card-img-top" alt=""><?php endif; ?>
        <div class="card-body">
          <div class="small text-muted"><?= e($v['category_name']) ?></div>
          <h3 class="h6"><?= e($v['make'] . ' ' . $v['model']) ?></h3>
          <div><?= e(\Crms\Money::format($v['daily_rate'])) ?> / day</div>
          <span class="badge text-bg-secondary badge-status"><?= e($v['status']) ?></span>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
