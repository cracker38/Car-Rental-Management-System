<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $isHome = (string) ($_GET['r'] ?? 'home') === 'home'; ?>
  <meta name="theme-color" content="#1b1d1f">
  <meta name="description" content="<?= e($metaDescription ?? 'Discover Rwanda with Rent Car Rwanda. Book reliable 4x4s, SUVs and chauffeur-ready vehicles in Kigali.') ?>">
  <title><?= e($title ?? 'Rent Car Rwanda') ?> &middot; Car hire in Kigali</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= e(asset('public/css/app.css')) ?>" rel="stylesheet">
</head>
<body class="site-body<?= $isHome ? ' home-body' : '' ?><?= !empty($printReceipt) ? ' receipt-print' : '' ?>">
<?php require dirname(__DIR__) . '/partials/site-nav.php'; ?>
<?php foreach ($flashes as $f): ?>
  <div class="container pt-3"><div class="alert alert-<?= e($f['type']) ?> mb-0"><?= e($f['message']) ?></div></div>
<?php endforeach; ?>
<?php if (!empty($fullBleed)): ?>
  <?= $content ?>
<?php else: ?>
  <div class="site-page container py-4 py-lg-5">
    <h1 class="page-title"><?= e($title ?? '') ?></h1>
    <?= $content ?>
  </div>
<?php endif; ?>
<footer class="site-footer<?= $isHome ? ' home-footer' : '' ?>">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="footer-brand"><span class="brand-mark"><i class="bi bi-car-front-fill" aria-hidden="true"></i></span> Rent Car Rwanda</div>
        <p>Good cars, local care, and more ways to see Rwanda. Find us in Kigali.</p>
      </div>
      <div class="col-md-4">
        <div class="footer-title">Discover</div>
        <a href="<?= e(url('fleet')) ?>">Our fleet</a>
        <a href="<?= e(url('search')) ?>">Find a car</a>
        <a href="<?= e(url('services')) ?>">Services</a>
        <a href="<?= e(url('blog')) ?>">Rwanda journal</a>
        <a href="<?= e(url('about')) ?>">About us</a>
        <a href="<?= e(url('contact')) ?>">Contact</a>
      </div>
      <div class="col-md-4">
        <div class="footer-title">Need a hand?</div>
        <p class="mb-1">Our team is happy to help plan your drive.</p>
        <a href="tel:+250785354935">0785354935</a>
        <a href="https://wa.me/250785354935" target="_blank" rel="noopener">Message us on WhatsApp</a>
      </div>
    </div>
    <div class="footer-copy">&copy; <?= date('Y') ?> Rent Car Rwanda <span>Kigali &middot; Rwanda</span></div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('public/js/app.js')) ?>"></script>
</body>
</html>
