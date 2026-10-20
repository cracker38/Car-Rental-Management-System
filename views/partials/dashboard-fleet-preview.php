<section class="panel mt-3">
  <div class="admin-panel-heading">
    <div><h2 class="panel-h mb-1">Fleet snapshot</h2><p class="small text-muted mb-0">Current vehicles and availability</p></div>
    <a class="small fw-bold" href="<?= e(url('vehicles')) ?>">Open fleet <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
  </div>
  <?php if (empty($fleetPreview)): ?><p class="text-muted mb-0">No vehicles are recorded yet.</p><?php else: ?>
    <div class="row g-3">
      <?php foreach ($fleetPreview as $vehicle): ?>
        <div class="col-6 col-md-3">
          <a class="dashboard-fleet-card d-block text-decoration-none" href="<?= e(url('vehicles/show', ['id' => $vehicle['vehicle_id']])) ?>">
            <img src="<?= e(vehicle_img($vehicle['image_path'] ?? null, $vehicle['category_name'] ?? null)) ?>" alt="<?= e($vehicle['make'] . ' ' . $vehicle['model']) ?>">
            <div class="dashboard-fleet-copy"><strong><?= e($vehicle['make'] . ' ' . $vehicle['model']) ?></strong><small><?= e($vehicle['registration_no']) ?> &middot; <?= e(ucfirst($vehicle['status'])) ?></small></div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
