<?php

declare(strict_types=1);

use App\Core\Migrator;

return static function (string $driver): array {
    return [
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS homework (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    teacher_id BIGINT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    attachment_path VARCHAR(255) NULL,
    requirements VARCHAR(500) NULL,
    due_at DATETIME NOT NULL,
    max_score DECIMAL(6,2) NOT NULL DEFAULT 20.00,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    INDEX idx_hw_class_due (class_id, due_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS homework_submissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    homework_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    submission_text TEXT NULL,
    attachment_path VARCHAR(255) NULL,
    submitted_at DATETIME NOT NULL,
    status ENUM('submitted','late','accepted','needs_revision') NOT NULL DEFAULT 'submitted',
    FOREIGN KEY (homework_id) REFERENCES homework(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY uq_hw_student_sub (homework_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS homework_feedback (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    submission_id BIGINT UNSIGNED NOT NULL UNIQUE,
    teacher_id BIGINT UNSIGNED NOT NULL,
    score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    feedback_text TEXT NULL,
    decision ENUM('accepted','needs_revision') NOT NULL DEFAULT 'accepted',
    graded_at DATETIME NOT NULL,
    FOREIGN KEY (submission_id) REFERENCES homework_submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS discussion_groups (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    class_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    description VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS discussion_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    group_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (group_id) REFERENCES discussion_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_dm_group_created (group_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS study_plans (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    counselor_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(160) NOT NULL,
    week_start_date DATE NOT NULL,
    week_end_date DATE NOT NULL,
    weekly_goal TEXT NULL,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (counselor_id) REFERENCES counselors(id) ON DELETE CASCADE,
    INDEX idx_sp_student_week (student_id, week_start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS study_plan_tasks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    study_plan_id BIGINT UNSIGNED NOT NULL,
    day_of_week ENUM('saturday','sunday','monday','tuesday','wednesday','thursday','friday') NOT NULL,
    subject_id INT UNSIGNED NULL,
    task_title VARCHAR(200) NOT NULL,
    start_time VARCHAR(10) NULL,
    end_time VARCHAR(10) NULL,
    duration_minutes INT NOT NULL DEFAULT 45,
    daily_goal VARCHAR(255) NULL,
    is_completed TINYINT(1) NOT NULL DEFAULT 0,
    completed_at DATETIME NULL,
    student_note VARCHAR(300) NULL,
    FOREIGN KEY (study_plan_id) REFERENCES study_plans(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
    ];
};
