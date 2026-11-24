<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class Database
{
    private static ?PDO $pdo = null;
    private static string $driver = 'mysql';

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $cfg = Config::get('database', []);
        $driver = (string) ($cfg['driver'] ?? 'mysql');
        self::$driver = $driver;

        if ($driver === 'sqlite') {
            $path = (string) ($cfg['sqlite_path'] ?? STORAGE_PATH . '/database.sqlite');
            if ($path !== ':memory:') {
                $dir = dirname($path);
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
            }
            $pdo = new PDO('sqlite:' . $path, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec('PRAGMA foreign_keys = ON;');
            self::$pdo = $pdo;
            return self::$pdo;
        }

        $host = (string) ($cfg['host'] ?? '127.0.0.1');
        $port = (int) ($cfg['port'] ?? 3306);
        $dbName = (string) ($cfg['database'] ?? 'edunexus_school');
        $username = (string) ($cfg['username'] ?? 'root');
        $password = (string) ($cfg['password'] ?? '');
        $charset = (string) ($cfg['charset'] ?? 'utf8mb4');

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $dbName, $charset);
        try {
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            self::$pdo = $pdo;
            return self::$pdo;
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public static function setConnection(PDO $pdo, string $driver = 'sqlite'): void
    {
        self::$pdo = $pdo;
        self::$driver = $driver;
    }

    public static function reset(): void
    {
        self::$pdo = null;
    }

    public static function driver(): string
    {
        if (self::$pdo === null) {
            self::connection();
        }
        return self::$driver;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function fetchColumn(string $sql, array $params = [], int $col = 0): mixed
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn($col);
    }

    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function insert(string $sql, array $params = []): int
    {
        $pdo = self::connection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $pdo->lastInsertId();
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        $nested = $pdo->inTransaction();
        if (!$nested) {
            $pdo->beginTransaction();
        }
        try {
            $result = $callback($pdo);
            if (!$nested) {
                $pdo->commit();
            }
            return $result;
        } catch (Throwable $e) {
            if (!$nested && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
