<?php

declare(strict_types=1);

use App\Core\Migrator;

return static function (string $driver): array {
    return [
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS schools (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(180) NOT NULL,
    city VARCHAR(100) NULL,
    address VARCHAR(255) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(191) NULL,
    director_user_id BIGINT UNSIGNED NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (director_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS counselor_assignments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    counselor_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    assigned_at DATETIME NOT NULL,
    FOREIGN KEY (counselor_id) REFERENCES counselors(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY uq_counselor_student (counselor_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS counseling_notes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    counselor_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    note TEXT NOT NULL,
    follow_up_date DATE NULL,
    visible_to_parent TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (counselor_id) REFERENCES counselors(id) ON DELETE CASCADE,
    INDEX idx_cn_student (student_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
    ];
};
