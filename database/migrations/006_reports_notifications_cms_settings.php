<?php

declare(strict_types=1);

use App\Core\Migrator;

return static function (string $driver): array {
    return [
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS report_cards (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    academic_year_id INT UNSIGNED NOT NULL,
    semester_id INT UNSIGNED NULL,
    report_type ENUM('monthly','semester','annual') NOT NULL DEFAULT 'monthly',
    period_label VARCHAR(100) NOT NULL,
    attendance_rate DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    homework_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    final_average DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    academic_status VARCHAR(60) NOT NULL DEFAULT 'Good',
    comments TEXT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    generated_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE SET NULL,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS report_card_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    report_card_id BIGINT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NULL,
    subject_grade DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    exam_average DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    homework_score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    remarks VARCHAR(300) NULL,
    FOREIGN KEY (report_card_id) REFERENCES report_cards(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS performance_scores (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('student','teacher') NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    academic_year_id INT UNSIGNED NOT NULL,
    period_label VARCHAR(80) NOT NULL,
    academic_score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    participation_score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    homework_score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    study_plan_score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    attendance_score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    improvement_score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    total_score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    category VARCHAR(60) NOT NULL,
    breakdown_json JSON NULL,
    calculated_at DATETIME NOT NULL,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
    INDEX idx_perf_lookup (entity_type, entity_id, academic_year_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(60) NOT NULL,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    link VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notif_user_read (user_id, is_read, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS announcements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    author_id BIGINT UNSIGNED NULL,
    target_role ENUM('all','teachers','students','parents','class') NOT NULL DEFAULT 'all',
    class_id INT UNSIGNED NULL,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    published_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(191) NOT NULL UNIQUE,
    summary VARCHAR(400) NULL,
    body TEXT NULL,
    event_date DATE NOT NULL,
    location VARCHAR(150) NULL,
    cover_image VARCHAR(255) NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS media (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    category ENUM('banner','gallery','news','event') NOT NULL DEFAULT 'gallery',
    file_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(200) NULL,
    uploaded_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS cms_posts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    type ENUM('news','banner','activity') NOT NULL DEFAULT 'news',
    title VARCHAR(180) NOT NULL,
    summary VARCHAR(400) NULL,
    body TEXT NULL,
    image_path VARCHAR(255) NULL,
    link_url VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 1,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    author_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(120) NOT NULL PRIMARY KEY,
    setting_value TEXT NULL,
    setting_group VARCHAR(60) NOT NULL DEFAULT 'general',
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
    ];
};
