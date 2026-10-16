<?php
declare(strict_types=1);

/**
 * Public HDEV callback. No CSRF, no session login.
 * Completing a payment always re-queries HDEV get_pay(); callback parameters are not trusted.
 */
require __DIR__ . '/src/bootstrap_callback.php';

use Crms\Database;
use Crms\Services\HdevPaymentService;

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input') ?: '';
$json = json_decode($raw, true);
if (!is_array($json)) {
    $json = [];
}

$ref = $_GET['tx_ref'] ?? $_GET['trx_ref'] ?? $_GET['transaction_ref'] ?? $_GET['ref']
    ?? $_POST['tx_ref'] ?? $_POST['trx_ref'] ?? $_POST['transaction_ref'] ?? $_POST['ref']
    ?? $json['tx_ref'] ?? $json['trx_ref'] ?? $json['transaction_ref'] ?? $json['ref'] ?? '';

$ref = is_string($ref) ? trim($ref) : '';
if ($ref === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing tx_ref']);
    exit;
}

try {
    $before = Database::fetch('SELECT * FROM financial_transactions WHERE external_reference = ?', [$ref]);
    $service = new HdevPaymentService();
    $row = $service->settleByReference($ref);
    if ($before && $row) {
        $service->notifyIfCompleted($before, $row);
    }
    echo json_encode([
        'status' => 'ok',
        'tx_ref' => $ref,
        'ledger_status' => $row['status'] ?? 'unknown',
    ]);
} catch (Throwable $e) {
    http_response_code(409);
    echo json_encode(['status' => 'error', 'message' => 'Could not settle payment.']);
}
