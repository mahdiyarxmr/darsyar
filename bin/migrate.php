<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Config;
use App\Core\Migrator;

$exportSql = in_array('--export-sql', $argv, true)
    ? BASE_PATH . '/database/schema_mysql.sql'
    : null;

if ($exportSql !== null) {
    Config::set('database.driver', 'mysql');
    $files = glob(BASE_PATH . '/database/migrations/*.php') ?: [];
    sort($files);
    $sqlChunks = ["-- DarsYar MySQL/MariaDB Schema Export"];
    foreach ($files as $file) {
        $migration = require $file;
        $sqlChunks[] = "-- " . basename($file);
        foreach ($migration('mysql') as $stmt) {
            $sqlChunks[] = rtrim($stmt, ';') . ';';
        }
    }
    file_put_contents($exportSql, implode("\n\n", $sqlChunks) . "\n");
    echo "Exported MySQL schema to {$exportSql}\n";
    exit(0);
}

$executed = Migrator::migrate();
if ($executed === []) {
    echo "All migrations are already applied.\n";
} else {
    foreach ($executed as $name) {
        echo "Applied: {$name}\n";
    }
}
