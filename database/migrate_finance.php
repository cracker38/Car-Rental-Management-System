<?php
declare(strict_types=1);

/**
 * Additive migration for existing installations using the finance approval,
 * adjustment, and reconciliation workflow. Run with XAMPP PHP from the project
 * root: php database/migrate_finance.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$cfg = require dirname(__DIR__) . '/config/database.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name'], $cfg['port']);
    $db->set_charset('utf8mb4');

    $tableCheck = $db->query("SHOW TABLES LIKE 'financial_transactions'");
    if ($tableCheck->num_rows === 0) {
        throw new RuntimeException('financial_transactions table is missing; apply the main schema first.');
    }

    $columns = [];
    $columnRows = $db->query('SHOW COLUMNS FROM financial_transactions');
    while ($column = $columnRows->fetch_assoc()) {
        $columns[$column['Field']] = $column;
    }

    $addColumns = [
        'adjustment_direction' => "ALTER TABLE financial_transactions ADD COLUMN adjustment_direction ENUM('credit','debit') NULL",
        'approval_requested_by' => 'ALTER TABLE financial_transactions ADD COLUMN approval_requested_by BIGINT UNSIGNED NULL',
        'approval_reviewed_by' => 'ALTER TABLE financial_transactions ADD COLUMN approval_reviewed_by BIGINT UNSIGNED NULL',
        'approval_reviewed_at' => 'ALTER TABLE financial_transactions ADD COLUMN approval_reviewed_at DATETIME NULL',
        'approval_note' => 'ALTER TABLE financial_transactions ADD COLUMN approval_note VARCHAR(500) NULL',
        'reconciled_at' => 'ALTER TABLE financial_transactions ADD COLUMN reconciled_at DATETIME NULL',
        'reconciled_by' => 'ALTER TABLE financial_transactions ADD COLUMN reconciled_by BIGINT UNSIGNED NULL',
        'reconciliation_note' => 'ALTER TABLE financial_transactions ADD COLUMN reconciliation_note VARCHAR(500) NULL',
    ];
    foreach ($addColumns as $name => $sql) {
        if (!isset($columns[$name])) {
            $db->query($sql);
        }
    }

    $status = $db->query("SHOW COLUMNS FROM financial_transactions LIKE 'status'")->fetch_assoc();
    if (!$status) {
        throw new RuntimeException('financial_transactions.status is missing.');
    }
    if (str_starts_with(strtolower($status['Type']), 'enum(')) {
        preg_match_all("/'((?:[^']|'')*)'/", $status['Type'], $matches);
        $values = array_map(static fn(string $value): string => str_replace("''", "'", $value), $matches[1] ?? []);
        foreach (['awaiting_approval', 'rejected'] as $newValue) {
            if (!in_array($newValue, $values, true)) {
                $values[] = $newValue;
            }
        }
        $enum = implode(',', array_map(static fn(string $value): string => "'" . $db->real_escape_string($value) . "'", $values));
        $nullability = $status['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $status['Default'] === null
            ? ''
            : " DEFAULT '" . $db->real_escape_string((string) $status['Default']) . "'";
        $db->query("ALTER TABLE financial_transactions MODIFY COLUMN status ENUM({$enum}) {$nullability}{$default}");
    }

    $indexes = [];
    $indexRows = $db->query('SHOW INDEX FROM financial_transactions');
    while ($index = $indexRows->fetch_assoc()) {
        $indexes[$index['Key_name']] = true;
    }
    if (!isset($indexes['idx_fin_approval_status'])) {
        $db->query('ALTER TABLE financial_transactions ADD KEY idx_fin_approval_status (status,created_at)');
    }
    if (!isset($indexes['idx_fin_reconcile'])) {
        $db->query('ALTER TABLE financial_transactions ADD KEY idx_fin_reconcile (status,reconciled_at)');
    }

    $foreignKeys = [];
    $fkRows = $db->query(
        "SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS
         WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'financial_transactions'"
    );
    while ($foreignKey = $fkRows->fetch_assoc()) {
        $foreignKeys[$foreignKey['CONSTRAINT_NAME']] = true;
    }
    foreach ([
        'fk_fin_approval_requester' => 'ALTER TABLE financial_transactions ADD CONSTRAINT fk_fin_approval_requester FOREIGN KEY (approval_requested_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE',
        'fk_fin_approval_reviewer' => 'ALTER TABLE financial_transactions ADD CONSTRAINT fk_fin_approval_reviewer FOREIGN KEY (approval_reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE',
        'fk_fin_reconciler' => 'ALTER TABLE financial_transactions ADD CONSTRAINT fk_fin_reconciler FOREIGN KEY (reconciled_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE',
    ] as $name => $sql) {
        if (!isset($foreignKeys[$name])) {
            $db->query($sql);
        }
    }

    $db->query(
        "INSERT IGNORE INTO permissions (permission_key, description)
         VALUES ('transactions.approve','Approve or reject refund and financial adjustment requests')"
    );
    $db->query(
        "INSERT IGNORE INTO role_permissions (role_id, permission_id)
         SELECT r.role_id, p.permission_id FROM roles r JOIN permissions p
           ON p.permission_key = 'transactions.approve'
         WHERE r.role_name = 'Business Manager'"
    );

    $db->close();
    fwrite(STDOUT, "Finance schema migration applied.\n");
} catch (Throwable $error) {
    fwrite(STDERR, 'Finance schema migration failed: ' . $error->getMessage() . "\n");
    exit(1);
}
