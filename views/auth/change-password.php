<div class="row justify-content-center">
  <div class="col-md-8 col-lg-6">
    <?php if ($mustChangePassword): ?>
      <div class="alert alert-info" role="status">Your account has a temporary password. Set a new password to continue to your workspace.</div>
    <?php endif; ?>
    <section class="panel">
      <div class="panel-h">Change your password</div>
      <p class="small text-muted">Use at least 8 characters. You will need your current password to confirm this change.</p>
      <form method="post" action="<?= e(url('password/change')) ?>" autocomplete="on">
        <?= \Crms\Csrf::field() ?>
        <div class="mb-3">
          <label class="form-label" for="current-password">Current password</label>
          <input class="form-control" id="current-password" type="password" name="current_password" autocomplete="current-password" required>
        </div>
        <div class="mb-3">
          <label class="form-label" for="new-password">New password</label>
          <input class="form-control" id="new-password" type="password" name="new_password" minlength="8" autocomplete="new-password" required>
        </div>
        <div class="mb-3">
          <label class="form-label" for="confirm-password">Confirm new password</label>
          <input class="form-control" id="confirm-password" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required>
        </div>
        <button class="btn btn-primary" type="submit">Save new password</button>
      </form>
    </section>
  </div>
</div>
