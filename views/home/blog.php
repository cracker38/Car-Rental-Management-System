<main class="journal-page">
  <section class="journal-hero">
    <div class="container">
      <p class="journal-kicker"><span aria-hidden="true"></span> The Rwanda travel journal</p>
      <div class="journal-hero-grid">
        <div class="journal-hero-copy">
          <p class="eyebrow">Local insight. Better journeys.</p>
          <h1>See more of Rwanda, <span>at your own pace.</span></h1>
          <p class="journal-hero-intro">Thoughtful routes, practical car hire advice, and local ideas to help you make the most of every day on the road.</p>
          <div class="journal-hero-actions">
            <a class="journal-button journal-button-primary" href="<?= e(url('search')) ?>">Find your car <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
            <a class="journal-text-link" href="#journal-stories">Explore the journal <i class="bi bi-arrow-down" aria-hidden="true"></i></a>
          </div>
          <div class="journal-hero-note"><i class="bi bi-geo-alt" aria-hidden="true"></i><span>Made for the city, the countryside, and the miles between.</span></div>
        </div>
        <figure class="journal-hero-visual">
          <img src="<?= e(asset('public/img/fleet/SUV3.jpg')) ?>" alt="White SUV, one of the vehicle styles available in the fleet">
          <figcaption><span>01</span><span>Choose a car that fits the journey</span></figcaption>
          <span class="journal-photo-label"><i class="bi bi-compass" aria-hidden="true"></i> Rwanda, your way</span>
        </figure>
      </div>
      <div class="journal-topic-row" aria-label="Journal topics">
        <span><i class="bi bi-buildings" aria-hidden="true"></i> Kigali city notes</span>
        <span><i class="bi bi-signpost-split" aria-hidden="true"></i> Beyond the city</span>
        <span><i class="bi bi-car-front" aria-hidden="true"></i> Vehicle guides</span>
      </div>
    </div>
  </section>

  <section id="journal-stories" class="section journal-section">
    <div class="container">
      <div class="journal-section-heading">
        <div>
          <p class="eyebrow">Ideas for the road</p>
          <h2>Make the journey part of the story.</h2>
        </div>
        <p>Start with a few helpful notes for planning your time in Rwanda, then choose the vehicle that suits your plans.</p>
      </div>

      <div class="row g-4 journal-story-grid">
        <div class="col-md-6 col-lg-4">
          <article class="journal-card">
            <a class="journal-card-image" href="#guide-kigali" aria-label="Read the Kigali city guide">
              <img src="<?= e(asset('public/img/fleet/hatchback1.jpg')) ?>" alt="Blue compact hatchback for getting around the city" loading="lazy">
              <span class="journal-image-index">01</span>
            </a>
            <div class="journal-card-body">
              <p class="journal-category">CITY NOTES <span>·</span> KIGALI</p>
              <h3><a href="#guide-kigali">A day at your own pace in Kigali</a></h3>
              <p>Group nearby stops, leave room for a long lunch, and keep your plans flexible as you explore the city.</p>
              <a class="journal-card-link" href="#guide-kigali">Read the guide <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
            </div>
          </article>
        </div>
        <div class="col-md-6 col-lg-4">
          <article class="journal-card">
            <a class="journal-card-image" href="#guide-road" aria-label="Read the longer drive guide">
              <img src="<?= e(asset('public/img/fleet/SUV2.jpg')) ?>" alt="Silver SUV suited to a longer drive" loading="lazy">
              <span class="journal-image-index">02</span>
            </a>
            <div class="journal-card-body">
              <p class="journal-category">COUNTRY ROADS <span>·</span> ROUTE PLANNING</p>
              <h3><a href="#guide-road">Make space for a longer drive</a></h3>
              <p>Plan a comfortable day beyond Kigali with a route, sensible breaks, and a return time that works.</p>
              <a class="journal-card-link" href="#guide-road">Read the guide <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
            </div>
          </article>
        </div>
        <div class="col-md-6 col-lg-4">
          <article class="journal-card">
            <a class="journal-card-image" href="#guide-car" aria-label="Read the vehicle selection guide">
              <img src="<?= e(asset('public/img/fleet/sedan2.jpg')) ?>" alt="Red sedan with room for a comfortable city trip" loading="lazy">
              <span class="journal-image-index">03</span>
            </a>
            <div class="journal-card-body">
              <p class="journal-category">TRIP PLANNING <span>·</span> PICK YOUR CAR</p>
              <h3><a href="#guide-car">Choose the right car for your plan</a></h3>
              <p>Think about passengers, luggage, where you are going, and how much time you will spend driving.</p>
              <a class="journal-card-link" href="#guide-car">Read the guide <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
            </div>
          </article>
        </div>
      </div>

      <div class="journal-guides">
        <div class="journal-guides-heading">
          <p class="eyebrow">A little more detail</p>
          <h2>Good plans leave room for discovery.</h2>
        </div>
        <article id="guide-kigali" class="journal-guide">
          <div class="journal-guide-title"><span>01 / CITY NOTES</span><h3>A day at your own pace in Kigali</h3></div>
          <div class="journal-guide-copy"><p>Choose the places you most want to see, then group nearby stops together. A flexible schedule leaves room for an unhurried lunch or an unexpected detour. If you are driving, allow time for traffic and set a return time that gives your day a comfortable finish.</p><a href="<?= e(url('search')) ?>">Plan a car hire <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
        </article>
        <article id="guide-road" class="journal-guide">
          <div class="journal-guide-title"><span>02 / COUNTRY ROADS</span><h3>Make space for a longer drive</h3></div>
          <div class="journal-guide-copy"><p>Before leaving Kigali, check your route and current conditions, choose a sensible departure time, and plan breaks. Share your intended return window when you search so you can select a hire period that fits the day.</p><a href="<?= e(url('search')) ?>">Check available cars <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
        </article>
        <article id="guide-car" class="journal-guide">
          <div class="journal-guide-title"><span>03 / TRIP PLANNING</span><h3>Choose the right car for your plan</h3></div>
          <div class="journal-guide-copy"><p>A compact car can suit city plans, while more passengers or luggage may call for extra space. For a route outside town, consider the road conditions and ask our Kigali team which available options fit your itinerary.</p><a href="<?= e(url('contact')) ?>">Talk to our team <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
        </article>
      </div>
    </div>
  </section>

  <section class="journal-cta">
    <div class="container journal-cta-inner">
      <div><p class="eyebrow">Ready when you are</p><h2>Start with the journey you have in mind.</h2><p>Browse the fleet or talk with our Kigali team about your plans.</p></div>
      <div class="journal-hero-actions"><a class="journal-button journal-button-light" href="<?= e(url('fleet')) ?>">Explore the fleet <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a><a class="journal-cta-phone" href="tel:+250785354935">0785354935</a></div>
    </div>
  </section>
</main>
