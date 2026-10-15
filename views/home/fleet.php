<section class="section">
  <div class="container">
    <h1 class="page-title">Our fleet</h1>
    <p class="intro">Browse categories used on the road in Rwanda. Staff can add, edit or replace any car — including its photo — from Operations → Vehicles.</p>
    <?php if (!$vehicles): ?>
      <div class="alert alert-info">The fleet is empty. Add sample cars in the backend after install.</div>
    <?php endif; ?>
    <div class="row g-4">
      <?php foreach ($vehicles as $vehicle): ?>
        <div class="col-md-6 col-lg-4"><?php $manage = !empty($user) && can('vehicles.manage'); $quote = null; $pickup = ''; $returnAt = ''; require dirname(__DIR__) . '/partials/vehicle-card.php'; ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
