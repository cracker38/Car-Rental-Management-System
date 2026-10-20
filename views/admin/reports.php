<?php
$summaryIcons = [
    'fleet vehicles' => 'bi-car-front',
    'confirmed or completed bookings' => 'bi-calendar-check',
    'rental pickups' => 'bi-key',
    'completed payments' => 'bi-wallet2',
    'payment refunds' => 'bi-arrow-counterclockwise',
    'net payments' => 'bi-graph-up-arrow',
    'net deposit movement' => 'bi-safe2',
    'awaiting approvals' => 'bi-shield-check',
    'my reservations' => 'bi-journal-text',
    'confirmed reservations' => 'bi-calendar-check',
    'rejected reservations' => 'bi-calendar-x',
    'assigned trips' => 'bi-person-badge',
    'completed trips' => 'bi-check2-circle',
    'delayed trips' => 'bi-clock-history',
    'maintenance jobs' => 'bi-wrench-adjustable',
    'open jobs' => 'bi-tools',
    'completed jobs' => 'bi-check2-circle',
    'recorded costs' => 'bi-cash-coin',
];
$rowStart = $reportTotalRows > 0 ? (($reportPage - 1) * $pageSize) + 1 : 0;
$rowEnd = min($reportTotalRows, $reportPage * $pageSize);
?>
<div class="report-print-area">
  <header class="report-heading">
    <div class="report-heading-copy">
      <div class="report-eyebrow"><i class="bi bi-bar-chart-line" aria-hidden="true"></i><?= e($reportModeLabel) ?> report</div>
      <h1><?= e($title) ?></h1>
      <p>Review <?= e(strtolower($reportModeLabel)) ?> activity, totals and records for a selected reporting period.</p>
    </div>
    <div class="report-period-card">
      <span><i class="bi bi-calendar3" aria-hidden="true"></i> Reporting period</span>
      <strong><?= e(date('d M Y', strtotime($dateFrom))) ?> <span aria-hidden="true">&ndash;</span> <?= e(date('d M Y', strtotime($dateTo))) ?></strong>
      <small>Generated <?= e($generatedAt) ?></small>
    </div>
  </header>

  <form method="get" action="<?= e(url('reports')) ?>" class="report-filters panel">
    <input type="hidden" name="r" value="reports">
    <div class="report-filter-heading">
      <div>
        <div class="panel-h mb-1">Reporting period</div>
        <p class="small text-muted mb-0">Choose dates to refresh the summary and matching records.</p>
      </div>
      <a href="<?= e(url('reports')) ?>">Reset to this month</a>
    </div>
    <div class="row g-3 align-items-end">
      <div class="col-sm-5"><label class="form-label" for="report-from">From</label><input class="form-control" id="report-from" name="date_from" type="date" required value="<?= e($dateFrom) ?>"></div>
      <div class="col-sm-5"><label class="form-label" for="report-to">To</label><input class="form-control" id="report-to" name="date_to" type="date" required value="<?= e($dateTo) ?>"></div>
      <div class="col-sm-2 d-grid"><button class="btn btn-primary"><i class="bi bi-filter me-1" aria-hidden="true"></i>Apply dates</button></div>
    </div>
  </form>

  <div class="report-toolbar">
    <div class="report-results-note">
      <i class="bi bi-info-circle" aria-hidden="true"></i>
      <span>Summary totals cover the full reporting period<?php if (!$isFullReportOutput && $reportTotalRows > 0): ?>. Showing <?= number_format($rowStart) ?>&ndash;<?= number_format($rowEnd) ?> of <?= number_format($reportTotalRows) ?> records<?php endif; ?>.</span>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-outline-dark" href="<?= e(url('reports', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'export' => 'csv'])) ?>"><i class="bi bi-download" aria-hidden="true"></i> Export CSV</a>
      <a class="btn btn-outline-dark" href="<?= e(url('reports', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'print' => '1'])) ?>"><i class="bi bi-printer" aria-hidden="true"></i> Print / PDF</a>
    </div>
  </div>

  <?php if ($summary): ?>
    <section class="report-summary-grid" aria-label="Report summary">
      <?php foreach ($summary as $item): $iconKey = strtolower((string) $item['label']); ?>
        <article class="report-summary-card">
          <span class="report-summary-icon"><i class="bi <?= e($summaryIcons[$iconKey] ?? 'bi-clipboard-data') ?>" aria-hidden="true"></i></span>
          <div class="report-summary-copy">
            <div class="report-summary-label"><?= e($item['label']) ?></div>
            <div class="report-summary-value"><?= e((string) $item['value']) ?></div>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <?php if ($financialDetails && $reportMode === 'company'): ?>
    <section class="panel report-detail-panel mb-3">
      <div class="report-section-heading">
        <div><h2>Financial activity</h2><p>Completed ledger activity and approval requests in the selected dates.</p></div>
        <span class="report-section-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span>
      </div>
      <div class="table-responsive"><table class="table align-middle mb-0">
        <tbody><?php foreach ($financialDetails as [$label, $amount]): ?>
          <tr><th scope="row"><?= e($label) ?></th><td class="text-end fw-semibold"><?= e((string) $amount) ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
    </section>
  <?php endif; ?>

  <section class="panel report-table-panel">
    <div class="report-section-heading">
      <div><h2><?= e($reportMode === 'financial' ? 'Financial activity totals' : $title . ' records') ?></h2><p>Activity from <?= e($dateFrom) ?> through <?= e($dateTo) ?>, inclusive.</p></div>
      <span class="report-record-count"><i class="bi bi-list-ul" aria-hidden="true"></i><?= number_format($reportTotalRows) ?> <?= $reportTotalRows === 1 ? 'record' : 'records' ?></span>
    </div>
    <?php if (!$rows): ?>
      <div class="empty-state"><i class="bi bi-inbox" aria-hidden="true"></i><span><?= e($emptyMessage) ?></span></div>
    <?php else: ?>
      <div class="table-responsive"><table class="table report-data-table align-middle mb-0">
        <caption class="visually-hidden"><?= e($title) ?>, <?= e($dateFrom) ?> to <?= e($dateTo) ?></caption>
        <thead><tr><?php foreach ($columns as $column): ?><th scope="col"><?= e($column) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
          <?php foreach ($rows as $row): $reportCells = array_slice($row, 0, count($columns)); ?><tr><?php foreach ($reportCells as $index => $cell): ?>
            <td>
              <?php if ($reportMode === 'customer' && $index === 0 && isset($row['_reservation_id'])): ?>
                <a class="report-record-link" href="<?= e(url('reservations/show', ['id' => (int) $row['_reservation_id']])) ?>"><?= e((string) $cell) ?></a>
              <?php else: ?><?= e((string) $cell) ?><?php endif; ?>
            </td>
          <?php endforeach; ?></tr><?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>

    <?php if (!$isFullReportOutput && $reportPages > 1): ?>
      <nav class="report-pagination" aria-label="Report pages">
        <span>Page <?= number_format($reportPage) ?> of <?= number_format($reportPages) ?></span>
        <div class="d-flex gap-2">
          <?php if ($reportPage > 1): ?><a class="btn btn-sm btn-outline-dark" href="<?= e(url('reports', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'page' => $reportPage - 1])) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Previous</a><?php endif; ?>
          <?php if ($reportPage < $reportPages): ?><a class="btn btn-sm btn-outline-dark" href="<?= e(url('reports', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'page' => $reportPage + 1])) ?>">Next <i class="bi bi-arrow-right" aria-hidden="true"></i></a><?php endif; ?>
        </div>
      </nav>
    <?php endif; ?>
  </section>

  <?php if (!empty($autoPrintReport)): ?><script>window.addEventListener('load', function () { window.print(); });</script><?php endif; ?>
</div>
