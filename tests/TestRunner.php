<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Config;
use App\Core\Database;
use App\Core\Migrator;
use Database\Seeders\DatabaseSeeder;
use PDO;
use RuntimeException;

final class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;

    public static function bootFreshDatabase(): void
    {
        Config::set('database.driver', 'sqlite');
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON;');
        Database::setConnection($pdo, 'sqlite');
        Migrator::migrate();
        DatabaseSeeder::run();
    }

    public function assertTrue(bool $cond, string $label): void
    {
        if ($cond) {
            $this->passed++;
            echo "  [PASS] {$label}\n";
        } else {
            $this->failed++;
            echo "  [FAIL] {$label}\n";
        }
    }

    public function assertEquals(mixed $expected, mixed $actual, string $label): void
    {
        if ($expected == $actual) {
            $this->passed++;
            echo "  [PASS] {$label}\n";
        } else {
            $this->failed++;
            echo "  [FAIL] {$label} (expected " . var_export($expected, true) . ", got " . var_export($actual, true) . ")\n";
        }
    }

    public function summary(): int
    {
        echo "\n========================================\n";
        echo "Tests Passed: {$this->passed} | Failed: {$this->failed}\n";
        echo "========================================\n";
        return $this->failed === 0 ? 0 : 1;
    }
}
