<h2 class="h4 mb-3">Availability search</h2>
<p class="text-muted">A search result is not a lock. Confirmation rechecks conflicts inside a transaction with <code>SELECT ... FOR UPDATE</code>.</p>
<form class="row g-2 mb-4" method="get">
  <input type="hidden" name="r" value="search">
  <div class="col-md-3"><label class="form-label">Pickup</label><input class="form-control" type="datetime-local" name="pickup_datetime" value="<?= e(str_replace(' ', 'T', substr((string)$pickup, 0, 16))) ?>" required></div>
  <div class="col-md-3"><label class="form-label">Return</label><input class="form-control" type="datetime-local" name="return_datetime" value="<?= e(str_replace(' ', 'T', substr((string)$returnAt, 0, 16))) ?>" required></div>
  <div class="col-md-3"><label class="form-label">Category</label>
    <select class="form-select" name="category_id">
      <option value="">Any</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int)$c['category_id'] ?>" <?= (string)$categoryId === (string)$c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary">Search</button></div>
</form>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if ($pickup && $returnAt && !$error): ?>
  <?php if (!$results): ?><div class="alert alert-info">No vehicles are free for that period.</div><?php endif; ?>
  <div class="row g-3">
    <?php foreach ($results as $v): $q = $quotes[(int)$v['vehicle_id']] ?? null; ?>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <h3 class="h6"><?= e($v['make'] . ' ' . $v['model']) ?> · <?= e($v['registration_no']) ?></h3>
            <p class="small mb-2"><?= e($v['category_name']) ?> · <?= e($v['transmission']) ?> · <?= e($v['fuel_type']) ?></p>
            <?php if ($q): ?>
              <p class="mb-1"><strong><?= e(\Crms\Money::format($q['total'])) ?></strong> for <?= (int)$q['estimated_days'] ?> billable day(s)</p>
              <p class="small text-muted">Hire <?= e(\Crms\Money::format($q['estimated_amount'])) ?> + tax <?= e(\Crms\Money::format($q['tax_amount'])) ?></p>
            <?php endif; ?>
            <?php if (!empty($user)): ?>
              <a class="btn btn-sm btn-primary" href="<?= e(url('reservations/form', ['vehicle_id' => $v['vehicle_id'], 'pickup_datetime' => $pickup, 'return_datetime' => $returnAt])) ?>">Book</a>
            <?php else: ?>
              <a class="btn btn-sm btn-outline-primary" href="<?= e(url('login')) ?>">Sign in to book</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
