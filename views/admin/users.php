<p class="lead-muted mb-3">Create staff accounts and assign their role-based permissions here. Customers register on the website. Drivers also need a linked driver record.</p>
<form method="post" class="panel mb-4">
  <?= \Crms\Csrf::field() ?>
  <input type="hidden" name="action" value="create">
  <div class="panel-h">Create staff account</div>
  <div class="row g-2">
    <div class="col-md-3"><label class="form-label">First name</label><input class="form-control" name="first_name" required></div>
    <div class="col-md-3"><label class="form-label">Last name</label><input class="form-control" name="last_name" required></div>
    <div class="col-md-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div>
    <div class="col-md-3"><label class="form-label">Phone</label><input class="form-control" name="phone"></div>
    <div class="col-md-3"><label class="form-label">Temporary password</label><input class="form-control" type="password" name="password" minlength="8" required></div>
    <div class="col-md-3"><label class="form-label">Role</label><select class="form-select" name="role_id" required>
      <option value="">Choose a staff role</option>
      <?php foreach ($roles as $r): ?><?php if ($r['role_name'] !== 'Customer'): ?><option value="<?= (int)$r['role_id'] ?>"><?= e($r['role_name']) ?></option><?php endif; ?><?php endforeach; ?>
    </select></div>
    <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active">active</option><option value="inactive">inactive</option><option value="suspended">suspended</option><option value="pending">pending</option></select></div>
  </div>
  <button class="btn btn-primary mt-3">Create account</button>
</form>
<div class="panel table-panel">
  <div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if (!$users): ?><tr><td colspan="5"><div class="empty-state">No accounts yet.</div></td></tr><?php endif; ?>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= e($u['first_name'] . ' ' . $u['last_name']) ?></td>
        <td><?= e((string)$u['email']) ?></td>
        <td>
          <?php $formId = 'user-form-' . (int) $u['user_id']; ?>
          <form id="<?= e($formId) ?>" method="post">
            <?= \Crms\Csrf::field() ?>
            <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
          </form>
          <select class="form-select form-select-sm" name="role_id" form="<?= e($formId) ?>" aria-label="Role for <?= e($u['email']) ?>">
            <?php foreach ($roles as $r): ?>
              <option value="<?= (int)$r['role_id'] ?>" <?= (int)$r['role_id'] === (int)$u['role_id'] ? 'selected' : '' ?>><?= e($r['role_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td>
          <select class="form-select form-select-sm" name="status" form="<?= e($formId) ?>" aria-label="Status for <?= e($u['email']) ?>">
            <?php foreach (['active','inactive','suspended','pending'] as $s): ?>
              <option value="<?= $s ?>" <?= $u['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><button class="btn btn-sm btn-primary" form="<?= e($formId) ?>">Save</button></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
