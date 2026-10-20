<p class="lead-muted mb-3">Manage staff and customer login accounts here. New Rental Officer customers appear in the user list; older customer records needing a login appear below. Temporary passwords must be changed at first sign-in. Driver accounts also need a linked driver record.</p>
<?php if (!empty($unlinkedCustomers)): ?>
<div class="panel table-panel mb-4">
  <div class="panel-h">Rental customers without a login</div>
  <div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Customer</th><th>Email</th><th>Phone</th><th>Customer status</th><th>Login setup</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($unlinkedCustomers as $customer): ?>
      <?php $validCustomerEmail = filter_var((string) ($customer['email'] ?? ''), FILTER_VALIDATE_EMAIL) !== false; ?>
      <tr>
        <td><?= e($customer['first_name'] . ' ' . $customer['last_name']) ?><small class="d-block text-muted"><?= e($customer['customer_code']) ?></small></td>
        <td><?= e((string) ($customer['email'] ?? '')) ?: '-' ?></td>
        <td><?= e($customer['phone']) ?></td>
        <td><?= e(ucfirst((string) $customer['status'])) ?></td>
        <td><?php if (!$validCustomerEmail): ?>Add a valid email<?php elseif ($customer['login_setup_status'] === 'email_in_use'): ?>Email already belongs to an account<?php else: ?>Ready to create<?php endif; ?></td>
        <td>
          <?php if (!$validCustomerEmail || $customer['login_setup_status'] === 'email_in_use'): ?>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('customers/form', ['id' => (int) $customer['customer_id']])) ?>">Edit customer</a>
          <?php else: ?>
            <form method="post" class="m-0">
              <?= \Crms\Csrf::field() ?>
              <input type="hidden" name="action" value="create_customer_login">
              <input type="hidden" name="customer_id" value="<?= (int) $customer['customer_id'] ?>">
              <button class="btn btn-sm btn-primary">Create login</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>
<form method="post" class="panel mb-4">
  <?= \Crms\Csrf::field() ?>
  <input type="hidden" name="action" value="create">
  <div class="panel-h">Create staff account</div>
  <div class="row g-2">
    <div class="col-md-3"><label class="form-label">First name</label><input class="form-control" name="first_name" required></div>
    <div class="col-md-3"><label class="form-label">Last name</label><input class="form-control" name="last_name" required></div>
    <div class="col-md-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div>
    <div class="col-md-3"><label class="form-label">Phone</label><input class="form-control" id="new-user-phone" name="phone"></div>
    <div class="col-md-3"><label class="form-label">Temporary password</label><input class="form-control" type="password" name="password" minlength="8" required></div>
    <div class="col-md-3"><label class="form-label" for="new-user-role">Role</label><select class="form-select" id="new-user-role" name="role_id" required>
      <option value="">Choose a staff role</option>
      <?php foreach ($roles as $r): ?><?php if ($r['role_name'] !== 'Customer'): ?><option value="<?= (int)$r['role_id'] ?>" data-role-name="<?= e($r['role_name']) ?>"><?= e($r['role_name']) ?></option><?php endif; ?><?php endforeach; ?>
    </select></div>
    <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active">active</option><option value="inactive">inactive</option><option value="suspended">suspended</option><option value="pending">pending</option></select></div>
    <div class="col-12 d-none" id="new-driver-profile-fields">
      <div class="row g-2 rounded border bg-light p-2 mx-0">
        <div class="col-md-6"><label class="form-label" for="new-driver-license">Driving licence number</label><input class="form-control" id="new-driver-license" name="driving_license_number" maxlength="100"><small class="form-text">Required to make a Driver account available for scheduling.</small></div>
        <div class="col-md-6"><label class="form-label" for="new-driver-license-expiry">Licence expiry date</label><input class="form-control" id="new-driver-license-expiry" name="license_expiry_date" type="date"></div>
      </div>
    </div>
  </div>
  <button class="btn btn-primary mt-3">Create account</button>
</form>
<script>
(() => {
  const role = document.getElementById('new-user-role');
  const fields = document.getElementById('new-driver-profile-fields');
  const phone = document.getElementById('new-user-phone');
  const license = document.getElementById('new-driver-license');
  if (!role || !fields || !phone || !license) return;
  const syncDriverFields = () => {
    const isDriver = role.selectedOptions[0]?.dataset.roleName === 'Driver';
    fields.classList.toggle('d-none', !isDriver);
    phone.required = isDriver;
    license.required = isDriver;
  };
  role.addEventListener('change', syncDriverFields);
  syncDriverFields();
})();
</script>
<div class="panel table-panel">
  <div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>First name</th><th>Last name</th><th>Email</th><th>Phone</th><th>Role</th><th>Account status</th><th>New password</th><th></th></tr></thead>
    <tbody>
    <?php if (!$users): ?><tr><td colspan="8"><div class="empty-state"><?= ($q ?? '') !== '' ? 'No user accounts match this search.' : 'No accounts yet.' ?></div></td></tr><?php endif; ?>
    <?php foreach ($users as $u): ?>
      <tr>
        <?php $formId = 'user-form-' . (int) $u['user_id']; ?>
        <td>
          <form id="<?= e($formId) ?>" method="post">
            <?= \Crms\Csrf::field() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
          </form>
          <input class="form-control form-control-sm" name="first_name" value="<?= e($u['first_name']) ?>" maxlength="100" required form="<?= e($formId) ?>" aria-label="First name for <?= e($u['email'] ?? ('user ' . $u['user_id'])) ?>">
        </td>
        <td><input class="form-control form-control-sm" name="last_name" value="<?= e($u['last_name']) ?>" maxlength="100" required form="<?= e($formId) ?>" aria-label="Last name for <?= e($u['email'] ?? ('user ' . $u['user_id'])) ?>"></td>
        <td><input class="form-control form-control-sm" type="email" name="email" value="<?= e((string)$u['email']) ?>" maxlength="190" required form="<?= e($formId) ?>" aria-label="Email for <?= e($u['first_name'] . ' ' . $u['last_name']) ?>"></td>
        <td><input class="form-control form-control-sm" name="phone" value="<?= e((string)$u['phone']) ?>" maxlength="30" form="<?= e($formId) ?>" aria-label="Phone for <?= e($u['first_name'] . ' ' . $u['last_name']) ?>"></td>
        <td>
          <select class="form-select form-select-sm" name="role_id" form="<?= e($formId) ?>" aria-label="Role for <?= e($u['email']) ?>">
            <?php foreach ($roles as $r): ?>
              <option value="<?= (int)$r['role_id'] ?>" <?= (int)$r['role_id'] === (int)$u['role_id'] ? 'selected' : '' ?>><?= e($r['role_name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (($u['role_name'] ?? '') === 'Driver' && ($u['status'] ?? '') === 'active' && empty($u['driver_id'])): ?>
            <small class="d-block text-warning-emphasis mt-1">Driver profile missing. <a href="<?= e(url('drivers/form', ['user_id' => (int) $u['user_id']])) ?>">Complete profile</a></small>
          <?php endif; ?>
        </td>
        <td>
          <select class="form-select form-select-sm" name="status" form="<?= e($formId) ?>" aria-label="Status for <?= e($u['email']) ?>">
            <?php foreach (['active' => 'Active', 'inactive' => 'Deactivated', 'suspended' => 'Suspended', 'pending' => 'Pending'] as $s => $label): ?>
              <option value="<?= $s ?>" <?= $u['status'] === $s ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><input class="form-control form-control-sm" type="password" name="new_password" minlength="8" autocomplete="new-password" form="<?= e($formId) ?>" placeholder="Leave blank to keep" aria-label="Set a new password for <?= e($u['email']) ?>"></td>
        <td><button class="btn btn-sm btn-primary" form="<?= e($formId) ?>">Save</button></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
