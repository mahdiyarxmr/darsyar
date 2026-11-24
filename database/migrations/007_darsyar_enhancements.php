<?php

declare(strict_types=1);

use App\Core\Migrator;

return static function (string $driver): array {
    return [
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS study_plan_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    note VARCHAR(400) NULL,
    status ENUM('pending','fulfilled') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
    ];
};
