<form method="post" class="card card-body">
  <?= \Crms\Csrf::field() ?>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">First name</label><input class="form-control" name="first_name" required value="<?= e($customer['first_name'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label">Last name</label><input class="form-control" name="last_name" required value="<?= e($customer['last_name'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" required value="<?= e($customer['phone'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?= e($customer['email'] ?? '') ?>"></div>
    <div class="col-12"><label class="form-label">Address</label><input class="form-control" name="address" value="<?= e($customer['address'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">ID type</label>
      <select class="form-select" name="identification_type">
        <option value="">—</option>
        <?php foreach (['national_id','passport','other'] as $t): ?>
          <option value="<?= $t ?>" <?= ($customer['identification_type'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4"><label class="form-label">ID number</label><input class="form-control" name="identification_number" value="<?= e($customer['identification_number'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">Licence</label><input class="form-control" name="driving_license_number" value="<?= e($customer['driving_license_number'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">Licence expiry</label><input class="form-control" type="date" name="license_expiry_date" value="<?= e($customer['license_expiry_date'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">Status</label>
      <select class="form-select" name="status">
        <?php foreach (['active','inactive','blacklisted'] as $t): ?>
          <option value="<?= $t ?>" <?= ($customer['status'] ?? 'active') === $t ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <button class="btn btn-primary mt-3">Save</button>
</form>
