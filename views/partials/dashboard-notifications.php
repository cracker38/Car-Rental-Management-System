<?php if (!empty($notes)): ?>
  <section class="card card-body mt-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h2 class="h6 mb-0">Recent notifications</h2>
      <span class="small text-muted"><?= count($notes) ?> latest</span>
    </div>
    <div class="list-group list-group-flush">
      <?php foreach ($notes as $note): ?>
        <div class="list-group-item px-0">
          <div class="d-flex justify-content-between gap-3"><strong><?= e($note['subject'] ?? $note['notification_type']) ?></strong><small class="text-muted text-nowrap"><?= e($note['created_at']) ?></small></div>
          <p class="small text-muted mb-0 mt-1"><?= e($note['message']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>
