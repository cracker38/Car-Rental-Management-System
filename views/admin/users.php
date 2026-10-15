<div class="card table-responsive"><table class="table mb-0">
  <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><?= e($u['first_name'] . ' ' . $u['last_name']) ?></td>
      <td><?= e((string)$u['email']) ?></td>
      <td>
        <form method="post" class="d-flex gap-1">
          <?= \Crms\Csrf::field() ?>
          <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
          <select class="form-select form-select-sm" name="role_id">
            <?php foreach ($roles as $r): ?>
              <option value="<?= (int)$r['role_id'] ?>" <?= $r['role_id'] === $u['role_id'] ? 'selected' : '' ?>><?= e($r['role_name']) ?></option>
            <?php endforeach; ?>
          </select>
          <select class="form-select form-select-sm" name="status">
            <?php foreach (['active','inactive','suspended','pending'] as $s): ?>
              <option value="<?= $s ?>" <?= $u['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-sm btn-primary">Save</button>
        </form>
      </td>
      <td><?= e($u['status']) ?></td>
      <td></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
