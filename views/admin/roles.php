<div class="admin-section-heading">
  <div>
    <p class="eyebrow mb-1">Access control</p>
    <h1 class="h3 mb-1">Roles &amp; Permissions</h1>
    <p class="text-muted mb-0">Each role has a defined permission set. Assign a role to a staff account to grant its access.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(url('admin/users')) ?>"><i class="bi bi-people me-1" aria-hidden="true"></i>Manage users</a>
</div>
<div class="row g-3">
  <?php foreach ($roles as $role): ?>
    <div class="col-md-6 col-xl-4">
      <section class="panel role-permission-card h-100">
        <div class="role-permission-heading">
          <span class="admin-role-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></span>
          <div><h2 class="h6 mb-1"><?= e($role['name']) ?></h2><p class="small text-muted mb-0"><?= e($role['description'] ?? '') ?></p></div>
        </div>
        <div class="role-permission-count"><?= count($role['permissions']) ?> permissions</div>
        <?php if ($role['permissions']): ?>
          <ul class="role-permission-list mb-0">
            <?php foreach ($role['permissions'] as $permission): ?>
              <li><strong><?= e($permission['key']) ?></strong><span><?= e($permission['description'] ?? '') ?></span></li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="small text-muted mb-0">No permissions assigned.</p>
        <?php endif; ?>
      </section>
    </div>
  <?php endforeach; ?>
</div>
