<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Migrator
{
    public static function migrate(?string $exportSqlPath = null): array
    {
        $driver = Database::driver();
        $pdo = Database::connection();

        self::ensureMigrationsTable($pdo, $driver);

        $applied = Database::fetchAll('SELECT migration FROM migrations');
        $appliedSet = array_flip(array_column($applied, 'migration'));

        $files = glob(BASE_PATH . '/database/migrations/*.php') ?: [];
        sort($files);

        $executed = [];
        $exportedSql = [];

        foreach ($files as $file) {
            $name = basename($file);
            $migration = require $file;
            $statements = $migration($driver);

            if ($exportSqlPath !== null) {
                $exportedSql[] = "-- Migration: {$name}";
                foreach ($statements as $sql) {
                    $exportedSql[] = rtrim($sql, ';') . ';';
                }
            }

            if (isset($appliedSet[$name])) {
                continue;
            }

            foreach ($statements as $sql) {
                $pdo->exec($sql);
            }

            Database::execute('INSERT INTO migrations (migration, applied_at) VALUES (?, ?)', [
                $name,
                date('Y-m-d H:i:s'),
            ]);
            $executed[] = $name;
        }

        if ($exportSqlPath !== null) {
            file_put_contents($exportSqlPath, implode("\n\n", $exportedSql) . "\n");
        }

        return $executed;
    }

    public static function ddl(string $driver, string $mysqlSql, ?string $sqliteSql = null): string
    {
        if ($driver === 'sqlite') {
            if ($sqliteSql !== null) {
                return $sqliteSql;
            }
            $sql = $mysqlSql;
            $sql = preg_replace('/\bBIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY\b/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
            $sql = preg_replace('/\bINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY\b/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
            $sql = preg_replace('/\bBIGINT UNSIGNED\b/i', 'INTEGER', $sql);
            $sql = preg_replace('/\bINT UNSIGNED\b/i', 'INTEGER', $sql);
            $sql = preg_replace('/\bTINYINT\(1\)\b/i', 'INTEGER', $sql);
            $sql = preg_replace('/\bENUM\([^)]+\)/i', 'TEXT', $sql);
            $sql = preg_replace('/\bJSON\b/i', 'TEXT', $sql);
            $sql = preg_replace('/\bDATETIME\b/i', 'TEXT', $sql);
            $sql = preg_replace('/\bUNIQUE\s+KEY\s+[a-zA-Z0-9_]+\s*\(/i', 'UNIQUE (', $sql);
            $sql = preg_replace('/\)\s*ENGINE=InnoDB[^;]*/i', ')', $sql);
            $sql = preg_replace('/,\s*INDEX\s+[a-zA-Z0-9_]+\s*\([^)]+\)/i', '', $sql);
            return $sql;
        }
        return $mysqlSql;
    }

    private static function ensureMigrationsTable(PDO $pdo, string $driver): void
    {
        if ($driver === 'sqlite') {
            $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (id INTEGER PRIMARY KEY AUTOINCREMENT, migration TEXT NOT NULL UNIQUE, applied_at TEXT NOT NULL)');
        } else {
            $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, migration VARCHAR(191) NOT NULL UNIQUE, applied_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        }
    }
}
