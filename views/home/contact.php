<main class="public-info-page">
  <section class="info-hero info-hero-compact">
    <div class="container info-hero-inner">
      <p class="eyebrow">We are here to help</p>
      <h1>Let's plan<br><span>your next drive.</span></h1>
      <p>Tell us a little about your trip. Our Kigali team will get back to you.</p>
    </div>
  </section>
  <section class="section contact-section">
    <div class="container">
      <div class="row g-4 g-lg-5 align-items-stretch">
        <div class="col-lg-7">
          <form method="post" class="contact-form-card">
            <?= \Crms\Csrf::field() ?>
            <div class="contact-form-heading"><span class="booking-icon"><i class="bi bi-send" aria-hidden="true"></i></span><div><h2>Send an enquiry</h2><p>We usually reply during Kigali business hours.</p></div></div>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label" for="contact-name">Your name</label><input class="form-control" id="contact-name" name="name" autocomplete="name" maxlength="120" required></div>
              <div class="col-md-6"><label class="form-label" for="contact-details">Email or phone</label><input class="form-control" id="contact-details" name="contact" autocomplete="email" maxlength="190" required></div>
              <div class="col-12"><label class="form-label" for="contact-message">How can we help?</label><textarea class="form-control" id="contact-message" name="message" rows="5" maxlength="5000" required></textarea></div>
              <div class="col-12"><button class="btn btn-primary" type="submit">Send message <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></button></div>
            </div>
          </form>
        </div>
        <div class="col-lg-5">
          <aside class="contact-details-card">
            <p class="eyebrow">Rent Car Rwanda</p><h2>Speak with our team</h2>
            <p>Tell us your dates, preferred vehicle, or any questions about your booking.</p>
            <a class="contact-detail-link" href="tel:+250785354935"><span><i class="bi bi-telephone" aria-hidden="true"></i></span><div><small>Call us</small><strong>0785354935</strong></div><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
            <a class="contact-detail-link" href="https://wa.me/250785354935" target="_blank" rel="noopener"><span><i class="bi bi-whatsapp" aria-hidden="true"></i></span><div><small>WhatsApp</small><strong>Message our team</strong></div><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
            <div class="contact-location"><i class="bi bi-geo-alt" aria-hidden="true"></i><span>Kigali, Rwanda</span></div>
          </aside>
        </div>
      </div>
    </div>
  </section>
</main>
