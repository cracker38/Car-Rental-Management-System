<?php
$stockPhotos = [
    'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=1200&q=85',
    'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=1200&q=85',
    'https://images.unsplash.com/photo-1560958089-b8a1929cea89?auto=format&fit=crop&w=1200&q=85',
];
?>
<main class="home-landing">
  <section class="hero-site" id="home">
    <div class="hero-shade"></div>
    <div class="hero-content container">
      <div class="hero-copy">
        <div class="hero-kicker"><span></span> Your journey starts in Rwanda</div>
        <p class="hero-phone"><i class="bi bi-telephone-fill" aria-hidden="true"></i><a href="tel:+250785354935">0785354935</a></p>
        <br><br>
        <br><br><br><br>
        <br><br><br><br>
        <br><br>
        <p class="hero-description">Explore the beauty of Rwanda with confidence and ease. Find the right car for a city stay, a business trip, or the open road.</p>
        <div class="hero-actions">
          <a class="btn btn-hero-primary" href="#booking">Find your car <i class="bi bi-arrow-down-right" aria-hidden="true"></i></a>
          <a class="btn btn-hero-ghost" href="<?= e(url('fleet')) ?>">Explore the fleet</a>
        </div>
      </div>

      <form class="booking-panel" id="booking" method="get" action="<?= e(url('search')) ?>">
        <input type="hidden" name="r" value="search">
        <div class="booking-heading">
          <span class="booking-icon"><i class="bi bi-calendar2-check" aria-hidden="true"></i></span>
          <div><strong>Plan your trip</strong><small>Choose your dates to see available cars</small></div>
        </div>
        <div class="booking-fields">
          <label class="booking-field"><span>Pick-up date &amp; time</span><input type="datetime-local" name="pickup_datetime" required data-min-now></label>
          <label class="booking-field"><span>Return date &amp; time</span><input type="datetime-local" name="return_datetime" required data-min-now></label>
          <label class="booking-field booking-category"><span>Vehicle type</span><select name="category_id"><option value="">Any vehicle</option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['category_id'] ?>"><?= e($c['category_name']) ?></option><?php endforeach; ?></select></label>
          <button class="btn btn-booking" type="submit">Search cars <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        </div>
      </form>

      <a class="hero-scroll" href="#featured"><span></span> Scroll to explore</a>
      <div class="hero-index" aria-hidden="true"><b>01</b><span></span> RWANDA · <?= date('Y') ?></div>
    </div>
  </section>

  <section class="section featured-section" id="featured">
    <div class="container">
      <div class="section-heading-row">
        <div><p class="eyebrow">A good trip starts here</p><h2>Featured vehicles</h2><p class="intro">A considered collection, ready for the city and the road beyond.</p></div>
        <a class="text-link" href="<?= e(url('fleet')) ?>">View all vehicles <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
      </div>
      <?php if (!$vehicles): ?>
        <div class="fleet-empty"><i class="bi bi-car-front" aria-hidden="true"></i><h3>Our fleet is getting ready</h3><p>Check back soon or contact our Kigali team to plan your trip.</p><a class="btn btn-outline-dark" href="<?= e(url('contact')) ?>">Contact us</a></div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach (array_slice($vehicles, 0, 3) as $index => $vehicle):
            $fallbackPhoto = $stockPhotos[$index % count($stockPhotos)];
            $imagePath = !empty($vehicle['image_path']) ? vehicle_img($vehicle['image_path']) : $fallbackPhoto;
            $isAvailable = ($vehicle['status'] ?? '') === 'available';
          ?>
            <div class="col-md-6 col-lg-4">
              <article class="home-vehicle-card">
                <a class="home-vehicle-photo" href="<?= e(url('search', ['category_id' => (int)$vehicle['category_id']])) ?>" aria-label="Find availability for <?= e(($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? '')) ?>">
                  <img src="<?= e($imagePath) ?>" alt="<?= e(($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? '')) ?>" loading="lazy">
                  <span class="vehicle-tag"><?= e($vehicle['category_name'] ?? 'Vehicle') ?></span>
                  <span class="vehicle-arrow"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
                </a>
                <div class="home-vehicle-info">
                  <div class="vehicle-title-row"><h3><?= e(($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? '')) ?></h3><span class="availability-dot<?= $isAvailable ? ' is-available' : '' ?>" title="<?= $isAvailable ? 'Available' : e(ucfirst((string)($vehicle['status'] ?? 'Unavailable'))) ?>"></span></div>
                  <p class="vehicle-specs"><span><?= e(ucfirst((string)($vehicle['transmission'] ?? '')) ?: 'Automatic') ?></span><i></i><span><?= e(ucfirst((string)($vehicle['fuel_type'] ?? '')) ?: 'Petrol') ?></span></p>
                  <div class="vehicle-bottom"><p class="home-vehicle-price"><strong><?= e(\Crms\Money::format($vehicle['daily_rate'] ?? 0)) ?></strong><span>/ day</span></p><a href="<?= e(url('search', ['category_id' => (int)$vehicle['category_id']])) ?>">Check availability <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
                </div>
              </article>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="journey-section" id="how-it-works">
    <div class="container journey-grid">
      <div class="journey-intro"><p class="eyebrow">The road, made easy</p><h2>More time for<br>the good parts.</h2><p>From your first search to the moment you set off, our Kigali-based team keeps every detail simple.</p><a class="btn btn-dark-solid" href="<?= e(url('about')) ?>">Get to know us <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></div>
      <div class="journey-steps">
        <article class="journey-step"><span>01</span><div><h3>Choose your dates</h3><p>See cars that fit your travel dates and vehicle needs.</p></div><i class="bi bi-calendar2-week" aria-hidden="true"></i></article>
        <article class="journey-step"><span>02</span><div><h3>Send a reservation</h3><p>Pick your preferred car and share a few trip details.</p></div><i class="bi bi-key" aria-hidden="true"></i></article>
        <article class="journey-step"><span>03</span><div><h3>Enjoy Rwanda</h3><p>We will help you get on the road with a clear plan.</p></div><i class="bi bi-signpost-split" aria-hidden="true"></i></article>
      </div>
    </div>
  </section>

  <section class="home-notes" id="rwanda-notes">
    <div class="container home-notes-inner"><div><p class="eyebrow">Made for the journey</p><h2>From Kigali, wherever Rwanda takes you.</h2><p>Thoughtful service, dependable vehicles, and local knowledge for the miles ahead.</p></div><a class="btn btn-hero-primary" href="<?= e(url('contact')) ?>">Talk to our team <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></div>
  </section>

  <a class="whatsapp-float" href="https://wa.me/250785354935" target="_blank" rel="noopener" aria-label="Chat with Rent Car Rwanda on WhatsApp"><i class="bi bi-whatsapp" aria-hidden="true"></i></a>
  <div class="chat-prompt" id="chatPrompt"><a href="https://wa.me/250785354935" target="_blank" rel="noopener">Chat with us <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a><button type="button" aria-label="Dismiss chat prompt" data-dismiss-chat><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
</main>
