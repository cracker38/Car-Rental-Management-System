<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Report') ?> &middot; Rent Car Rwanda</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= e(asset('public/css/app.css') . '?v=' . (string) filemtime(dirname(__DIR__, 2) . '/public/css/app.css')) ?>" rel="stylesheet">
  <style>
    body.report-print { background: #fff; }
    .report-print-document { max-width: 1180px; margin: 2rem auto; padding: 0 1rem; }
    @media print {
      .report-print-document { max-width: none; margin: 0; padding: 0; }
    }
  </style>
</head>
<body class="site-body app-body report-print">
  <main class="app-content report-print-document"><?= $content ?></main>
</body>
</html>
