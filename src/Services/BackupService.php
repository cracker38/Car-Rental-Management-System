<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Audit;
use Crms\Database;
use RuntimeException;

final class BackupService
{
    public function exportSql(): string
    {
        $pdo = Database::pdo();
        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
        $out = "-- CRMS backup " . date('c') . "\nSET FOREIGN_KEY_CHECKS=0;\n";
        foreach ($tables as $table) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $table)) {
                continue;
            }
            $create = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch();
            $out .= "DROP TABLE IF EXISTS `{$table}`;\n" . $create['Create Table'] . ";\n";
            $rows = $pdo->query('SELECT * FROM `' . $table . '`')->fetchAll();
            foreach ($rows as $row) {
                $cols = array_map(fn ($c) => '`' . $c . '`', array_keys($row));
                $vals = array_map(function ($v) use ($pdo) {
                    return $v === null ? 'NULL' : $pdo->quote((string) $v);
                }, array_values($row));
                $out .= 'INSERT INTO `' . $table . '` (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ");\n";
            }
        }
        $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
        $dir = dirname(__DIR__, 2) . '/storage/backups';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create backup directory.');
        }
        $file = $dir . '/crms-' . date('Ymd-His') . '.sql';
        file_put_contents($file, $out);
        Audit::log('backup.created', 'backup', basename($file), 'SQL export written');
        return $file;
    }
}
