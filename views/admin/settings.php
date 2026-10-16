<?php
$byKey = [];
foreach ($rows as $r) {
    $byKey[$r['setting_key']] = $r;
}
$hdevKeys = ['hdev_callback_url', 'hdev_sms_sender', 'hdev_sms_enabled'];
$opsKeys = ['business_name', 'tax_rate_percent', 'default_deposit_amount', 'turnaround_minutes', 'extra_km_rate', 'included_km_per_day', 'late_fee_per_hour', 'reservation_confirmation_required'];
$shown = array_merge($opsKeys, $hdevKeys);
?>
<div class="row g-4">
  <div class="col-lg-7">
    <form method="post" class="panel">
      <?= \Crms\Csrf::field() ?>
      <p class="eyebrow">Business</p>
      <h2 class="h4 mb-3">Pricing and operations</h2>
      <?php foreach ($opsKeys as $key): if (empty($byKey[$key])) continue; $r = $byKey[$key]; ?>
        <div class="mb-3">
          <label class="form-label"><?= e(str_replace('_', ' ', $r['setting_key'])) ?></label>
          <input class="form-control" name="<?= e($r['setting_key']) ?>" value="<?= e((string)$r['setting_value']) ?>">
          <div class="form-text"><?= e((string)$r['description']) ?></div>
        </div>
      <?php endforeach; ?>
      <?php foreach ($rows as $r): if (in_array($r['setting_key'], $shown, true)) continue; ?>
        <div class="mb-3">
          <label class="form-label"><?= e(str_replace('_', ' ', $r['setting_key'])) ?></label>
          <?php if ((int)$r['is_sensitive'] === 1): ?>
            <input class="form-control" name="<?= e($r['setting_key']) ?>" value="" placeholder="Stored (hidden)">
          <?php else: ?>
            <input class="form-control" name="<?= e($r['setting_key']) ?>" value="<?= e((string)$r['setting_value']) ?>">
          <?php endif; ?>
          <div class="form-text"><?= e((string)$r['description']) ?></div>
        </div>
      <?php endforeach; ?>
      <p class="eyebrow mt-4">HDEV</p>
      <h2 class="h4 mb-3">Payment and SMS</h2>
      <?php if (!empty($byKey['hdev_callback_url'])): $r = $byKey['hdev_callback_url']; ?>
        <div class="mb-3">
          <label class="form-label">Public callback URL</label>
          <input class="form-control" name="hdev_callback_url" value="<?= e((string)$r['setting_value']) ?>" placeholder="https://your-host/CarrentalMS/hdev_callback.php">
          <div class="form-text">HDEV cannot reach localhost. Use a public HTTPS URL of hdev_callback.php.</div>
        </div>
      <?php endif; ?>
      <?php if (!empty($byKey['hdev_sms_sender'])): $r = $byKey['hdev_sms_sender']; ?>
        <div class="mb-3">
          <label class="form-label">SMS sender ID</label>
          <input class="form-control" name="hdev_sms_sender" value="<?= e((string)$r['setting_value']) ?>">
          <div class="form-text">Must be an HDEV-approved sender ID.</div>
        </div>
      <?php endif; ?>
      <?php if (!empty($byKey['hdev_sms_enabled'])): $r = $byKey['hdev_sms_enabled']; ?>
        <div class="mb-3">
          <label class="form-label">Send customer SMS</label>
          <select class="form-select" name="hdev_sms_enabled">
            <option value="true" <?= $r['setting_value'] === 'true' ? 'selected' : '' ?>>Enabled</option>
            <option value="false" <?= $r['setting_value'] !== 'true' ? 'selected' : '' ?>>Disabled</option>
          </select>
        </div>
      <?php endif; ?>
      <p class="small text-muted">API IDs and keys stay in <code>config/hdev.php</code>.</p>
      <button class="btn btn-primary">Save settings</button>
    </form>
  </div>
  <div class="col-lg-5">
    <form method="post" class="panel momo-card">
      <?= \Crms\Csrf::field() ?>
      <input type="hidden" name="_action" value="test_sms">
      <p class="eyebrow">SMS check</p>
      <h2 class="h4">Send a test message</h2>
      <p class="text-muted">Confirms HDEV SMS credentials, sender ID, and the phone format in one step.</p>
      <div class="mb-3"><label class="form-label">Phone</label><input class="form-control" name="test_phone" required placeholder="07xxxxxxxx" inputmode="tel"></div>
      <div class="mb-3"><label class="form-label">Message</label><input class="form-control" name="test_message" value="Rent Car Rwanda SMS test."></div>
      <button class="btn btn-outline-dark">Send test SMS</button>
    </form>
  </div>
</div>
