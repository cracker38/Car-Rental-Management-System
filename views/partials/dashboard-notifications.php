<?php if (!empty($notes) || ($role ?? '') === 'Rental Officer'): ?>
  <section class="panel mt-4">
    <div class="panel-h d-flex justify-content-between align-items-center"><?= e($dashboardNotificationHeading ?? 'Recent activity') ?> <span class="small text-muted fw-normal"><?= count($notes) ?> latest</span></div>
    <?php if (empty($notes)): ?><p class="text-muted mb-0">No new reservation notifications.</p><?php endif; ?>
    <?php foreach ($notes as $note): ?>
      <div class="mini-row">
        <strong><?= e($note['subject'] ?? $note['notification_type']) ?></strong>
        <span><?= e($note['message']) ?></span>
        <span><?= e($note['created_at']) ?><?php if (!empty($note['channel'])): ?> · <?= e($note['channel']) ?><?php endif; ?><?php if (!empty($note['status'])): ?> · <?= e($note['status']) ?><?php endif; ?></span>
      </div>
    <?php endforeach; ?>
  </section>
<?php endif; ?>
