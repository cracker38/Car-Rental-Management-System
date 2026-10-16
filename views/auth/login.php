<section class="auth-wrap">
  <div class="container" style="max-width:440px">
    <div class="auth-card">
      <p class="eyebrow">Rent Car Rwanda</p>
      <h1 class="h3 mb-2">Sign in</h1>
      <p class="text-muted">Customers continue to trips and invoices. Staff open the workspace.</p>
      <form method="post" action="<?= e(url('login')) ?>">
        <?= \Crms\Csrf::field() ?>
        <div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required value="<?= e((string)\Crms\Http::old('email')) ?>"></div>
        <div class="mb-3"><label class="form-label">Password</label><input class="form-control" type="password" name="password" required></div>
        <button class="btn btn-accent w-100">Continue</button>
      </form>
      <p class="mt-3 mb-0">New here? <a href="<?= e(url('register')) ?>">Create an account</a></p>
    </div>
  </div>
</section>
