<section class="panel mt-4" aria-labelledby="dashboard-notifications-title" data-notification-poll="<?= e(url('notifications/poll')) ?>" data-unread="<?= (int) ($unreadNotificationCount ?? 0) ?>">
  <div class="panel-h d-flex justify-content-between align-items-center gap-2">
    <span id="dashboard-notifications-title"><?= e($dashboardNotificationHeading ?? 'Notifications') ?></span>
    <span class="badge text-bg-<?= (int) ($unreadNotificationCount ?? 0) > 0 ? 'primary' : 'secondary' ?>" id="dashboard-notification-count">
      <?= (int) ($unreadNotificationCount ?? 0) ?> unread
    </span>
  </div>
  <?php if (empty($notes)): ?>
    <p class="text-muted mb-0">No notifications yet.</p>
  <?php else: ?>
    <?php foreach ($notes as $note): ?>
      <div class="mini-row d-flex flex-wrap justify-content-between align-items-start gap-3 <?= ($note['status'] ?? '') !== 'read' ? 'border-start border-3 border-primary ps-3' : '' ?>">
        <div class="flex-grow-1">
          <strong><?= e($note['subject'] ?? $note['notification_type']) ?></strong>
          <span class="d-block"><?= nl2br(e($note['message'])) ?></span>
          <small class="text-muted"><?= e($note['created_at']) ?> · <?= e(ucwords(str_replace('_', ' ', $note['notification_type']))) ?> · <?= ($note['status'] ?? '') === 'read' ? 'Read' : 'Unread' ?></small>
        </div>
        <?php if (!empty($note['related_entity_type']) && !empty($note['related_entity_id'])): ?>
          <form method="post" action="<?= e(url('notifications/open')) ?>" class="flex-shrink-0">
            <?= \Crms\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= (int) $note['notification_id'] ?>">
            <button class="btn btn-sm btn-outline-primary">Open</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
<script>
(() => {
  const panel = document.querySelector('[data-notification-poll]');
  const count = document.getElementById('dashboard-notification-count');
  if (!panel || !count) return;
  const refresh = async () => {
    try {
      const response = await fetch(panel.dataset.notificationPoll, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
      if (!response.ok) return;
      const data = await response.json();
      const next = Number(data.unread || 0);
      const current = Number(panel.dataset.unread || 0);
      if (next > current) {
        window.location.reload();
        return;
      }
      panel.dataset.unread = String(next);
      count.textContent = `${next} unread`;
      count.classList.toggle('text-bg-primary', next > 0);
      count.classList.toggle('text-bg-secondary', next === 0);
    } catch (_) {}
  };
  window.setInterval(refresh, 30000);
})();
</script>
