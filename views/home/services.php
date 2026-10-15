<main class="public-info-page">
  <section class="info-hero">
    <div class="container info-hero-inner">
      <p class="eyebrow">A vehicle for every kind of trip</p>
      <h1>Choose how<br><span>you want to travel.</span></h1>
      <p>Book a car for Kigali, the countryside, or a trip that takes you further. Search by date to see current vehicle availability and rates.</p>
      <a class="btn btn-hero-primary mt-3" href="<?= e(url('search')) ?>">Search available cars <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
    </div>
  </section>
  <section class="section service-section">
    <div class="container">
      <div class="section-heading-row"><div><p class="eyebrow">Our services</p><h2>Set up for your journey.</h2><p class="intro">Pick the kind of hire that suits your plans, then send a request for your dates.</p></div></div>
      <div class="row g-4">
        <div class="col-md-6 col-lg-4"><article class="service-card"><span class="service-icon"><i class="bi bi-car-front" aria-hidden="true"></i></span><p class="service-number">01 / SELF-DRIVE</p><h3>Make your own way</h3><p>Choose a car from the fleet and explore at your own pace. Search for your dates to see what is available.</p><a href="<?= e(url('search')) ?>">Find a vehicle <i class="bi bi-arrow-right" aria-hidden="true"></i></a></article></div>
        <div class="col-md-6 col-lg-4"><article class="service-card service-card-featured"><span class="service-icon"><i class="bi bi-signpost-split" aria-hidden="true"></i></span><p class="service-number">02 / 4X4 &amp; SUV</p><h3>Go beyond the city</h3><p>Browse roomy SUVs and 4x4 options for family plans, group travel, and routes outside Kigali.</p><a href="<?= e(url('fleet')) ?>">Explore the fleet <i class="bi bi-arrow-right" aria-hidden="true"></i></a></article></div>
        <div class="col-md-6 col-lg-4"><article class="service-card"><span class="service-icon"><i class="bi bi-person-badge" aria-hidden="true"></i></span><p class="service-number">03 / CHAUFFEUR</p><h3>Travel with a driver</h3><p>Need a chauffeur? Contact our team with your itinerary and we will discuss the options for your trip.</p><a href="<?= e(url('contact')) ?>">Ask our team <i class="bi bi-arrow-right" aria-hidden="true"></i></a></article></div>
      </div>
    </div>
  </section>
  <section class="info-cta"><div class="container info-cta-inner"><div><p class="eyebrow">Ready when you are</p><h2>Start with your dates.</h2><p>Our search checks the fleet for your pick-up and return window.</p></div><a class="btn btn-hero-primary" href="<?= e(url('search')) ?>">Hire a car <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div></section>
</main>
