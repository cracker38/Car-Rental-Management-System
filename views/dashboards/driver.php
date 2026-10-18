<?php
$dashboardSubtitle = 'Your assigned chauffeur trips, schedule and trip updates.';
$dashboardNotificationHeading = 'Trip assignment notifications';
$dashboardActions = [
  ['route' => 'drivers/schedule', 'label' => 'Full schedule', 'icon' => 'bi-calendar2-week'],
];
require dirname(__DIR__) . '/partials/dashboard-heading.php';
require dirname(__DIR__) . '/partials/kpis.php';
require dirname(__DIR__) . '/partials/dashboard-shortcuts.php';
?>
<?php if (!empty($profile)): ?>
<section class="panel mb-3">
  <div class="panel-h">My driver profile</div>
  <div class="row g-3">
    <div class="col-md-3"><span class="text-muted small d-block">Name</span><strong><?= e($profile['first_name'] . ' ' . $profile['last_name']) ?></strong></div>
    <div class="col-md-3"><span class="text-muted small d-block">Phone</span><strong><?php if (!empty($profile['phone'])): ?><a href="tel:<?= e($profile['phone']) ?>"><?= e($profile['phone']) ?></a><?php else: ?>Not provided<?php endif; ?></strong></div>
    <div class="col-md-3"><span class="text-muted small d-block">Email</span><?php $profileEmail = $profile['email'] ?: ($profile['account_email'] ?? ''); ?><strong><?php if ($profileEmail !== ''): ?><a href="mailto:<?= e($profileEmail) ?>"><?= e($profileEmail) ?></a><?php else: ?>Not provided<?php endif; ?></strong></div>
    <div class="col-md-3"><span class="text-muted small d-block">Driving licence</span><strong><?= e($profile['driving_license_number'] ?? 'Not provided') ?></strong><?php if (!empty($profile['license_expiry_date'])): ?><small class="d-block text-muted">Expires <?= e($profile['license_expiry_date']) ?></small><?php endif; ?></div>
  </div>
</section>
<?php else: ?>
<div class="alert alert-warning">Your driver profile is not linked to this account. Contact an administrator to connect it to your login.</div>
<?php endif; ?>
<?php if (!empty($nextTrip)): ?>
<section class="panel invoice-hero mb-3">
  <?php $tripNow = time(); $tripPickupAt = strtotime((string)$nextTrip['pickup_datetime']); $tripReturnAt = strtotime((string)$nextTrip['return_datetime']); ?>
  <p class="eyebrow"><?= $tripReturnAt < $tripNow ? 'Overdue trip - follow up' : ($tripPickupAt < $tripNow ? 'Trip in progress' : 'Next pickup') ?></p>
  <h2><?= e($nextTrip['reservation_code']) ?></h2>
  <div class="kpi-row">
    <div><span>Pickup</span><strong><?= e($nextTrip['pickup_datetime']) ?></strong></div>
    <div><span>Return</span><strong><?= e($nextTrip['return_datetime']) ?></strong></div>
    <div><span>Customer</span><strong><?= e($nextTrip['first_name'] . ' ' . $nextTrip['last_name']) ?></strong></div>
    <div><span>Vehicle</span><strong><?= e($nextTrip['registration_no']) ?></strong></div>
    <div><span>Route</span><strong><?= e($nextTrip['pickup_location'] ?: 'Pickup pending') ?> to <?= e($nextTrip['return_location'] ?: 'Destination pending') ?></strong></div>
  </div>
  <p class="mb-3"><strong>Assignment status:</strong> <?= status_badge((string)($nextTrip['trip_status'] ?? 'assigned')) ?><?php if (!empty($nextTrip['assignment_notes'])): ?><span class="d-block mt-2"><strong>Trip instructions:</strong> <?= e($nextTrip['assignment_notes']) ?></span><?php endif; ?></p>
  <div class="d-flex flex-wrap gap-2">
    <?php if (!empty($nextTrip['phone'])): ?>
      <a class="btn btn-primary" href="tel:<?= e($nextTrip['phone']) ?>"><i class="bi bi-telephone"></i> Call customer</a>
      <a class="btn btn-outline-dark" href="https://wa.me/<?= e(preg_replace('/\D+/', '', (string)$nextTrip['phone'])) ?>" target="_blank" rel="noopener">WhatsApp</a>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>
<section class="panel table-panel">
  <div class="panel-h d-flex justify-content-between">My assigned trips <a class="small fw-normal" href="<?= e(url('drivers/schedule')) ?>">Open schedule</a></div>
  <?php if (!$assignments): ?><p class="text-muted mb-0">There are no upcoming or active trips assigned to your driver account.</p><?php else: ?>
    <div class="table-responsive"><table class="table align-middle mb-0">
      <thead><tr><th>Booking and instructions</th><th>Schedule</th><th>Customer</th><th>Vehicle</th><th>Route</th><th>Progress and report</th></tr></thead>
      <tbody><?php foreach ($assignments as $trip): ?><tr>
        <td><strong><?= e($trip['reservation_code']) ?></strong><?php if (!empty($trip['assignment_notes'])): ?><small class="d-block text-muted mt-1"><?= e($trip['assignment_notes']) ?></small><?php endif; ?></td>
        <td>Pickup: <?= e($trip['pickup_datetime']) ?><small class="d-block text-muted">Return: <?= e($trip['return_datetime']) ?></small></td>
        <td><?= e($trip['first_name'] . ' ' . $trip['last_name']) ?><?php if (!empty($trip['phone'])): ?><a class="d-block" href="tel:<?= e($trip['phone']) ?>"><?= e($trip['phone']) ?></a><?php endif; ?></td>
        <td><?= e($trip['make'] . ' ' . $trip['model']) ?><small class="d-block text-muted"><?= e($trip['registration_no']) ?></small></td>
        <td><?= e($trip['pickup_location'] ?: 'Pickup pending') ?><span class="d-block text-muted">to <?= e($trip['return_location'] ?: 'Destination pending') ?></span></td>
        <td><?= status_badge((string)($trip['trip_status'] ?? 'assigned')) ?><?php if (strtotime((string)$trip['return_datetime']) < time()): ?><small class="d-block text-danger fw-semibold mt-1">Return overdue</small><?php endif; ?><?php if (!empty($trip['status_updated_at'])): ?><small class="d-block text-muted mt-1">Updated <?= e($trip['status_updated_at']) ?></small><?php endif; ?>
          <?php if (!empty($trip['incident_report'])): ?><small class="d-block text-warning-emphasis mt-1"><strong>Reports:</strong> <?= nl2br(e($trip['incident_report'])) ?></small><?php endif; ?>
          <?php $back = 'dashboard'; require dirname(__DIR__) . '/partials/driver-trip-form.php'; ?>
        </td>
      </tr><?php endforeach; ?></tbody>
    </table></div>
  <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/partials/dashboard-notifications.php'; ?>
