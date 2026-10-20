<form method="get" class="report-filters panel mb-4">
  <input type="hidden" name="r" value="reports">
  <div class="panel-h">Reporting period</div>
  <div class="row g-3 align-items-end">
    <div class="col-sm-5"><label class="form-label" for="report-from">From</label><input class="form-control" id="report-from" name="date_from" type="date" required value="<?= e($dateFrom) ?>"></div>
    <div class="col-sm-5"><label class="form-label" for="report-to">To</label><input class="form-control" id="report-to" name="date_to" type="date" required value="<?= e($dateTo) ?>"></div>
    <div class="col-sm-2 d-grid"><button class="btn btn-primary">Apply dates</button></div>
  </div>
</form>
<div id="report-exports" class="report-toolbar d-flex flex-wrap gap-2 mb-4">
  <a class="btn btn-outline-dark" href="<?= e(url('reports', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'export' => 'csv'])) ?>"><i class="bi bi-download"></i> Export report (CSV)</a>
  <button class="btn btn-outline-dark" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Print / PDF</button>
</div>
<div class="report-print-area">
<?php if ($summary): ?>
  <div class="row g-3 mb-4">
    <?php foreach ($summary as $item): ?>
      <div class="col-md-6 col-xl-3"><div class="kpi-card"><div class="kpi-label"><?= e($item['label']) ?></div><div class="kpi-value"><?= e((string) $item['value']) ?></div></div></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php if ($reportMode === 'company' && $financialDetails): ?>
  <section class="panel table-panel mb-4">
    <div class="panel-h">Deposit and adjustment detail</div>
    <div class="table-responsive"><table class="table mb-0"><tbody>
      <?php foreach ($financialDetails as [$label, $amount]): ?>
        <tr><th scope="row"><?= e($label) ?></th><td><?= e(\Crms\Money::format($amount)) ?></td></tr>
      <?php endforeach; ?>
    </tbody></table></div>
  </section>
<?php endif; ?>
<section class="panel table-panel">
  <div class="panel-h"><?= e($title) ?></div>
  <p class="small text-muted">Activity in the selected reporting period: <?= e($dateFrom) ?> to <?= e($dateTo) ?>.</p>
  <?php if (!$rows): ?><div class="empty-state"><?= e($emptyMessage) ?></div>
  <?php else: ?>
    <div class="table-responsive"><table class="table align-middle mb-0">
      <thead><tr><?php foreach ($columns as $column): ?><th scope="col"><?= e($column) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): $reportCells = array_slice($row, 0, count($columns)); ?><tr><?php foreach ($reportCells as $index => $cell): ?>
          <td>
            <?php if ($reportMode === 'customer' && $index === 0 && isset($row[0])): ?>
              <a href="<?= e(url('reservations/show', ['id' => (int) ($row['_reservation_id'] ?? 0)])) ?>"><?= e((string) $cell) ?></a>
            <?php else: ?><?= e((string) $cell) ?><?php endif; ?>
          </td>
        <?php endforeach; ?></tr><?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</section>
<?php if (!empty($autoPrintReport)): ?>
<script>window.addEventListener('load', function () { window.print(); });</script>
<?php endif; ?>
</div>
