<?php
$dashboardSubtitle = 'Review vehicles held for maintenance and record a safe return-to-service decision.';
require dirname(__DIR__) . '/partials/dashboard-heading.php';
require dirname(__DIR__) . '/partials/kpis.php';
require dirname(__DIR__) . '/partials/dashboard-shortcuts.php';
?>
<section class="panel table-panel">
  <div class="panel-h">Vehicles currently in maintenance</div>
  <p class="px-3 text-muted">Rental scheduling and general fleet browsing are handled by operations staff. This dashboard shows only vehicles currently held out of service.</p>
  <div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Vehicle</th><th>Open jobs</th><th>Current work</th><th>Expected completion</th><th>Readiness</th></tr></thead>
    <tbody>
    <?php if (empty($maintenanceVehicles)): ?><tr><td colspan="5"><div class="empty-state">No vehicles are currently held for maintenance.</div></td></tr><?php endif; ?>
    <?php foreach ($maintenanceVehicles as $vehicle): ?>
      <tr>
        <td><strong><?= e($vehicle['registration_no']) ?></strong><small class="d-block text-muted"><?= e($vehicle['make'] . ' ' . $vehicle['model']) ?></small></td>
        <td><?= (int)$vehicle['open_jobs'] ?></td>
        <td><?= e($vehicle['current_job'] ?? 'No open job') ?></td>
        <td><?php if (!empty($vehicle['expected_completion_date'])): ?><?= e($vehicle['expected_completion_date']) ?><?php if ($vehicle['expected_completion_date'] < date('Y-m-d')): ?> <span class="badge text-bg-danger">Overdue</span><?php endif; ?><?php else: ?>Not set<?php endif; ?></td>
        <td><a class="btn btn-sm btn-outline-dark" href="<?= e(url('maintenance')) ?>">Review and update</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php require dirname(__DIR__) . '/partials/dashboard-notifications.php'; ?>
