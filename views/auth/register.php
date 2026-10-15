<div class="row justify-content-center">
  <div class="col-md-7">
    <div class="card"><div class="card-body p-4">
      <h1 class="h4 mb-3">Create a customer account</h1>
      <form method="post">
        <?= \Crms\Csrf::field() ?>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">First name</label><input class="form-control" name="first_name" required></div>
          <div class="col-md-6"><label class="form-label">Last name</label><input class="form-control" name="last_name" required></div>
          <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div>
          <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" required></div>
          <div class="col-12"><label class="form-label">Address</label><input class="form-control" name="address"></div>
          <div class="col-md-6"><label class="form-label">ID type</label>
            <select class="form-select" name="identification_type">
              <option value="">Optional</option>
              <option value="national_id">National ID</option>
              <option value="passport">Passport</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label">ID number</label><input class="form-control" name="identification_number"></div>
          <div class="col-md-6"><label class="form-label">Driving licence</label><input class="form-control" name="driving_license_number"></div>
          <div class="col-md-6"><label class="form-label">Password</label><input class="form-control" type="password" name="password" required minlength="8"></div>
          <div class="col-md-6"><label class="form-label">Confirm password</label><input class="form-control" type="password" name="password_confirm" required></div>
        </div>
        <button class="btn btn-primary mt-3">Register</button>
      </form>
    </div></div>
  </div>
</div>
