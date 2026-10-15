<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card"><div class="card-body p-4">
      <h1 class="h4 mb-3">Sign in</h1>
      <form method="post" action="<?= e(url('login')) ?>">
        <?= \Crms\Csrf::field() ?>
        <div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required value="<?= e((string)\Crms\Http::old('email')) ?>"></div>
        <div class="mb-3"><label class="form-label">Password</label><input class="form-control" type="password" name="password" required></div>
        <button class="btn btn-primary w-100">Sign in</button>
      </form>
      <p class="mt-3 mb-0 small">Need an account? <a href="<?= e(url('register')) ?>">Register</a></p>
    </div></div>
  </div>
</div>
