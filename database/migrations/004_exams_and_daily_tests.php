<?php

declare(strict_types=1);

use App\Core\Migrator;

return static function (string $driver): array {
    return [
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS question_bank (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    teacher_id BIGINT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    grade_id INT UNSIGNED NULL,
    type ENUM('mcq','descriptive') NOT NULL DEFAULT 'mcq',
    prompt TEXT NOT NULL,
    options_json JSON NULL,
    correct_answer VARCHAR(255) NULL,
    points DECIMAL(6,2) NOT NULL DEFAULT 1.00,
    difficulty ENUM('easy','medium','hard') NOT NULL DEFAULT 'medium',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (grade_id) REFERENCES grades(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS exams (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    exam_mode ENUM('online','offline') NOT NULL DEFAULT 'online',
    subject_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    academic_year_id INT UNSIGNED NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    duration_minutes INT NOT NULL DEFAULT 45,
    max_score DECIMAL(7,2) NOT NULL DEFAULT 20.00,
    negative_marking_ratio DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    randomize_questions TINYINT(1) NOT NULL DEFAULT 0,
    randomize_options TINYINT(1) NOT NULL DEFAULT 0,
    max_attempts INT NOT NULL DEFAULT 1,
    late_finish_policy ENUM('hard_cutoff','allow_finish_duration') NOT NULL DEFAULT 'hard_cutoff',
    practice_sheet_path VARCHAR(255) NULL,
    results_published TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('draft','published','closed') NOT NULL DEFAULT 'published',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
    INDEX idx_exams_class_mode (class_id, exam_mode, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS exam_questions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    exam_id BIGINT UNSIGNED NOT NULL,
    question_bank_id BIGINT UNSIGNED NULL,
    type ENUM('mcq','descriptive') NOT NULL DEFAULT 'mcq',
    prompt TEXT NOT NULL,
    points DECIMAL(6,2) NOT NULL DEFAULT 1.00,
    sort_order INT NOT NULL DEFAULT 1,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (question_bank_id) REFERENCES question_bank(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS exam_options (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    question_id BIGINT UNSIGNED NOT NULL,
    option_text VARCHAR(500) NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 1,
    FOREIGN KEY (question_id) REFERENCES exam_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS exam_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    exam_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    attempt_number INT NOT NULL DEFAULT 1,
    started_at DATETIME NOT NULL,
    submitted_at DATETIME NULL,
    expires_at DATETIME NOT NULL,
    question_order_json JSON NULL,
    status ENUM('in_progress','submitted','graded') NOT NULL DEFAULT 'in_progress',
    auto_score DECIMAL(7,2) NOT NULL DEFAULT 0.00,
    manual_score DECIMAL(7,2) NOT NULL DEFAULT 0.00,
    total_score DECIMAL(7,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY uq_exam_attempt_num (exam_id, student_id, attempt_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS exam_answers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    attempt_id BIGINT UNSIGNED NOT NULL,
    question_id BIGINT UNSIGNED NOT NULL,
    selected_option_id BIGINT UNSIGNED NULL,
    answer_text TEXT NULL,
    is_correct TINYINT(1) NULL,
    score_awarded DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    teacher_comment VARCHAR(500) NULL,
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES exam_questions(id) ON DELETE CASCADE,
    FOREIGN KEY (selected_option_id) REFERENCES exam_options(id) ON DELETE SET NULL,
    UNIQUE KEY uq_attempt_question (attempt_id, question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS exam_results (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    exam_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    attempt_id BIGINT UNSIGNED NULL,
    score DECIMAL(7,2) NOT NULL DEFAULT 0.00,
    max_score DECIMAL(7,2) NOT NULL DEFAULT 20.00,
    percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    remarks VARCHAR(500) NULL,
    graded_by BIGINT UNSIGNED NULL,
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE SET NULL,
    FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_exam_student_result (exam_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS daily_tests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS daily_test_questions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    daily_test_id BIGINT UNSIGNED NOT NULL,
    prompt TEXT NOT NULL,
    options_json JSON NOT NULL,
    correct_index INT NOT NULL DEFAULT 0,
    explanation TEXT NULL,
    FOREIGN KEY (daily_test_id) REFERENCES daily_tests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
        Migrator::ddl($driver, <<<SQL
CREATE TABLE IF NOT EXISTS daily_test_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    daily_test_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    total_questions INT NOT NULL DEFAULT 0,
    correct_count INT NOT NULL DEFAULT 0,
    incorrect_count INT NOT NULL DEFAULT 0,
    score_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    time_spent_seconds INT NOT NULL DEFAULT 0,
    answers_json JSON NULL,
    completed_at DATETIME NOT NULL,
    FOREIGN KEY (daily_test_id) REFERENCES daily_tests(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_dta_student (student_id, completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL),
    ];
};
