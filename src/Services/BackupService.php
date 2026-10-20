<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Audit;
use Crms\Database;
use RuntimeException;

final class BackupService
{
    /** Write a complete SQL export atomically so an interrupted job never looks like a valid backup. */
    public function exportSql(): string
    {
        $dir = $this->backupDirectory();
        $lock = @fopen($dir . '/.backup.lock', 'c');
        if ($lock === false) {
            throw new RuntimeException('Cannot open the backup lock file.');
        }
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            throw new RuntimeException('Another backup is already running.');
        }

        $filename = 'crms-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.sql';
        $temporary = $dir . '/.' . $filename . '.tmp';
        $target = $dir . '/' . $filename;
        $stream = @fopen($temporary, 'xb');
        if ($stream === false) {
            flock($lock, LOCK_UN);
            fclose($lock);
            throw new RuntimeException('Cannot create a temporary backup file.');
        }

        try {
            $pdo = Database::pdo();
            $this->write($stream, "-- CRMS backup " . date('c') . "\nSET FOREIGN_KEY_CHECKS=0;\n");
            $objects = $pdo->query('SHOW FULL TABLES')->fetchAll(\PDO::FETCH_NUM);
            $tables = [];
            $views = [];
            foreach ($objects as $object) {
                $name = (string) ($object[0] ?? '');
                $type = strtoupper((string) ($object[1] ?? ''));
                if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
                    continue;
                }
                if ($type === 'VIEW') {
                    $views[] = $name;
                } elseif ($type === 'BASE TABLE' || $type === 'TABLE') {
                    $tables[] = $name;
                }
            }

            // Remove views before the tables they depend on, then recreate them after the table data.
            foreach ($views as $view) {
                $this->write($stream, 'DROP VIEW IF EXISTS `' . $view . "`;\n");
            }

            foreach ($tables as $table) {
                $quotedTable = '`' . $table . '`';
                $createStatement = $pdo->query('SHOW CREATE TABLE ' . $quotedTable);
                $create = $createStatement->fetch(\PDO::FETCH_ASSOC);
                $createStatement->closeCursor();
                if (!$create || !isset($create['Create Table'])) {
                    throw new RuntimeException('Could not read the schema for table ' . $table . '.');
                }
                $this->write($stream, "DROP TABLE IF EXISTS {$quotedTable};\n" . $create['Create Table'] . ";\n");

                $statement = $pdo->query('SELECT * FROM ' . $quotedTable);
                $columns = null;
                while (($row = $statement->fetch(\PDO::FETCH_ASSOC)) !== false) {
                    if ($columns === null) {
                        $columns = array_map(
                            static fn(string $column): string => '`' . str_replace('`', '``', $column) . '`',
                            array_keys($row)
                        );
                    }
                    $values = [];
                    foreach (array_values($row) as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                            continue;
                        }
                        $quoted = $pdo->quote((string) $value);
                        if ($quoted === false) {
                            throw new RuntimeException('Could not encode a value while backing up ' . $table . '.');
                        }
                        $values[] = $quoted;
                    }
                    $this->write(
                        $stream,
                        'INSERT INTO ' . $quotedTable . ' (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ");\n"
                    );
                }
                $statement->closeCursor();
            }

            foreach ($views as $view) {
                $quotedView = '`' . $view . '`';
                $viewStatement = $pdo->query('SHOW CREATE VIEW ' . $quotedView);
                $viewDefinition = $viewStatement->fetch(\PDO::FETCH_ASSOC);
                $viewStatement->closeCursor();
                $createView = (string) ($viewDefinition['Create View'] ?? '');
                if ($createView === '') {
                    throw new RuntimeException('Could not read the definition for view ' . $view . '.');
                }
                // Avoid binding a restore to the database account that created this backup.
                $createView = preg_replace('/\sDEFINER\s*=\s*(?:`[^`]*`|[^\s]+)\s*@\s*(?:`[^`]*`|[^\s]+)\s*/i', ' ', $createView) ?? $createView;
                $this->write($stream, $createView . ";\n");
            }

            $this->write($stream, "SET FOREIGN_KEY_CHECKS=1;\n");
            if (!fflush($stream)) {
                throw new RuntimeException('Could not flush the completed backup to disk.');
            }
            if (!fclose($stream)) {
                $stream = false;
                throw new RuntimeException('Could not close the completed backup file.');
            }
            $stream = false;
            if (!@rename($temporary, $target)) {
                throw new RuntimeException('Could not finalize the completed backup file.');
            }
        } catch (\Throwable $error) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            if (is_file($temporary)) {
                @unlink($temporary);
            }
            flock($lock, LOCK_UN);
            fclose($lock);
            throw $error;
        }

        flock($lock, LOCK_UN);
        fclose($lock);
        Audit::log('backup.created', 'backup', basename($target), 'SQL export written');
        return $target;
    }

    /** Remove only CRMS-generated SQL exports older than the configured retention window. */
    public function pruneExpiredBackups(int $retentionDays = 30): int
    {
        if ($retentionDays < 1 || $retentionDays > 3650) {
            throw new RuntimeException('Backup retention must be between 1 and 3650 days.');
        }

        $dir = $this->backupDirectory();
        $cutoff = time() - ($retentionDays * 86400);
        $removed = 0;
        foreach (glob($dir . '/crms-*.sql') ?: [] as $path) {
            $name = basename($path);
            if (!preg_match('/^crms-\d{8}-\d{6}(?:-[a-f0-9]{6})?\.sql$/', $name) || is_link($path) || !is_file($path)) {
                continue;
            }
            $modified = filemtime($path);
            if ($modified !== false && $modified < $cutoff && @unlink($path)) {
                $removed++;
            }
        }
        if ($removed > 0) {
            Audit::log('backup.retention_pruned', 'backup', null, $removed . ' expired SQL backup(s) removed');
        }
        return $removed;
    }

    private function backupDirectory(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/backups';
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create backup directory.');
        }
        if (!is_writable($dir)) {
            throw new RuntimeException('The backup directory is not writable.');
        }
        return $dir;
    }

    /** Write all bytes even when the underlying stream performs a partial write. */
    private function write($stream, string $contents): void
    {
        $length = strlen($contents);
        $offset = 0;
        while ($offset < $length) {
            $written = fwrite($stream, substr($contents, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('Could not write the SQL backup.');
            }
            $offset += $written;
        }
    }
}
