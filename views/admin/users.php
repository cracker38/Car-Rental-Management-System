<p class="lead-muted mb-3">New customers register on the website. Staff register first, then you assign their role here. Drivers also need a linked driver record.</p>
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
