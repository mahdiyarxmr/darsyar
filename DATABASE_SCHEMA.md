# Database Schema — DarsYar (درسیار)

All modules share one normalized relational database (MySQL 8+ / MariaDB 10.4+, InnoDB, `utf8mb4_unicode_ci`), managed via 8 sequential migrations (`database/migrations/001`–`008`, 55 tables including `migrations`).

## Entity Groups & Relationships

### 1. Identity, Multi-School & RBAC
- `users`: `id`, `username` (unique), `email` (nullable unique), `phone`, `password_hash`, `full_name`, `locale` (`fa|en`, default `fa`), `theme` (`light|dark`, default `light`), `status` (`active|inactive`), `must_change_password`, `last_login_at`, timestamps.
- `roles`: `id`, `slug` (`super_admin`, `director`, `vice_principal`, `counselor`, `teacher`, `student`, `parent`), `name_key`.
- `permissions`: `id`, `slug` (unique), `module`, `description`.
- `role_permissions`: `role_id`, `permission_id` (composite PK).
- `user_roles`: `user_id`, `role_id` (unique pair).
- `schools`: `id`, `code` (unique), `name`, `city`, `address`, `phone`, `email`, `director_user_id` (nullable FK -> `users`), `status` (`active|inactive`), timestamps.
- `login_attempts`: `id`, `identifier`, `ip_address`, `attempted_at`, `success`.
- `audit_logs`: `id`, `user_id`, `action`, `entity_type`, `entity_id`, `metadata_json`, `ip_address`, `created_at`.

### 2. Profiles & Linkages
- `administrators`: `id`, `user_id` (unique FK), `title`, `department`.
- `teachers`: `id`, `user_id` (unique FK), `employee_code` (unique), `specialization`, `hire_date`.
- `counselors`: `id`, `user_id` (unique FK), `employee_code` (unique), `specialty`.
- `students`: `id`, `user_id` (unique FK), `student_code` (unique), `national_id`, `class_id` (FK -> `classes`), `guardian_phone`, `date_of_birth`, `enrollment_date`.
- `parents`: `id`, `user_id` (unique FK), `national_id`, `occupation`, `emergency_phone`.
- `parent_student`: `id`, `parent_id` (FK -> `parents`), `student_id` (FK -> `students`), `relationship` (`father|mother|guardian`), `is_primary` — **UNIQUE(`parent_id`, `student_id`)** prevents duplicates while allowing siblings per parent.
- `counselor_assignments`: `id`, `counselor_id` (FK -> `counselors`), `student_id` (FK -> `students`), `assigned_at` — **UNIQUE(`counselor_id`, `student_id`)**.

### 3. Academic Structure & Schedules
- `academic_years`: `id`, `name`, `start_date`, `end_date`, `is_active`.
- `semesters`: `id`, `academic_year_id`, `name`, `start_date`, `end_date`, `is_active`.
- `grades`: `id`, `name`, `level_order`.
- `classes`: `id`, `academic_year_id`, `grade_id`, `name`, `capacity`, `room_number`.
- `subjects`: `id`, `code` (unique), `name`, `description`.
- `class_subjects`: `id`, `class_id`, `subject_id`, `weekly_hours` — UNIQUE(`class_id`, `subject_id`).
- `teacher_assignments`: `id`, `teacher_id`, `class_id`, `subject_id`, `academic_year_id` — UNIQUE(`teacher_id`, `class_id`, `subject_id`, `academic_year_id`).
- `timetables`: `id`, `academic_year_id`, `class_id`, `title`, `file_path`, `schedule_json`, `uploaded_by`, timestamps.

### 4. Attendance
- `attendance`: `id`, `student_id`, `class_id`, `subject_id` (nullable for school-wide daily attendance), `attendance_date`, `scope` (`school|classroom`), `status` (`present|absent|late|excused`), `note`, `recorded_by`, timestamps.

### 5. Online Classrooms (Skyroom)
- `online_class_sessions`: `id`, `teacher_id`, `class_id`, `subject_id`, `title`, `description`, `scheduled_start`, `scheduled_end`, `provider` (`skyroom|external`), `external_room_id`, `join_url`, `fallback_url`, `status` (`scheduled|live|completed|cancelled`), timestamps.
- `online_class_attendance`: `id`, `session_id`, `student_id`, `joined_at`, `duration_minutes`, `status` (`joined|absent`) — UNIQUE(`session_id`, `student_id`).

### 6. Examinations, Question Bank & Daily Practice Tests
- `question_bank`: `id`, `teacher_id`, `subject_id`, `grade_id`, `type` (`mcq|descriptive`), `prompt`, `options_json`, `correct_answer`, `points`, `difficulty`, timestamps.
- `exams`: `id`, `title`, `description`, `exam_mode` (`online|offline`), `subject_id`, `class_id`, `teacher_id`, `academic_year_id`, `starts_at`, `ends_at`, `duration_minutes`, `max_score`, `negative_marking_ratio`, `randomize_questions`, `randomize_options`, `max_attempts`, `late_finish_policy` (`hard_cutoff|allow_finish_duration`), `practice_sheet_path`, `results_published`, `status` (`draft|published|closed`), timestamps.
- `exam_questions`: `id`, `exam_id`, `question_bank_id` (nullable), `type` (`mcq|descriptive`), `prompt`, `points`, `sort_order`.
- `exam_options`: `id`, `question_id`, `option_text`, `is_correct`, `sort_order`.
- `exam_attempts`: `id`, `exam_id`, `student_id`, `attempt_number`, `started_at`, `submitted_at`, `expires_at`, `question_order_json`, `status` (`in_progress|submitted|graded`), `auto_score`, `manual_score`, `total_score`.
- `exam_answers`: `id`, `attempt_id`, `question_id`, `selected_option_id` (nullable), `answer_text` (nullable), `is_correct` (nullable), `score_awarded`, `teacher_comment`.
- `exam_results`: `id`, `exam_id`, `student_id`, `attempt_id` (nullable for offline), `score`, `max_score`, `percentage`, `remarks`, `graded_by`, `published_at`, timestamps — UNIQUE(`exam_id`, `student_id`).
- `daily_tests`: `id`, `title`, `subject_id`, `class_id` (nullable = open to subject), `teacher_id`, `description`, `is_active`, timestamps.
- `daily_test_questions`: `id`, `daily_test_id`, `prompt`, `options_json`, `correct_index`, `explanation`.
- `daily_test_attempts`: `id`, `daily_test_id`, `student_id`, `total_questions`, `correct_count`, `incorrect_count`, `score_percentage`, `time_spent_seconds`, `answers_json`, `completed_at`.

### 7. Homework & Discussions
- `homework`: `id`, `teacher_id`, `class_id`, `subject_id`, `title`, `description`, `attachment_path`, `requirements`, `due_at`, `max_score`, timestamps.
- `homework_submissions`: `id`, `homework_id`, `student_id`, `submission_text`, `attachment_path`, `submitted_at`, `status` (`submitted|late|accepted|needs_revision`) — UNIQUE(`homework_id`, `student_id`).
- `homework_feedback`: `id`, `submission_id`, `teacher_id`, `score`, `feedback_text`, `decision` (`accepted|needs_revision`), `graded_at`.
- `discussion_groups`: `id`, `class_id`, `subject_id`, `teacher_id`, `title`, `description`, `created_at`.
- `discussion_messages`: `id`, `group_id`, `user_id`, `message`, `created_at`.

### 8. Counseling & Weekly Study Plans
- `study_plans`: `id`, `student_id`, `counselor_id`, `title`, `week_start_date`, `week_end_date`, `weekly_goal`, `status` (`active|archived`), timestamps.
- `study_plan_tasks`: `id`, `study_plan_id`, `day_of_week` (`saturday..friday`), `subject_id` (nullable), `task_title`, `start_time`, `end_time`, `duration_minutes`, `daily_goal`, `is_completed`, `completed_at`, `student_note`.
- `study_plan_requests`: `id`, `student_id`, `note`, `status` (`pending|fulfilled`), timestamps.
- `counseling_notes`: `id`, `student_id`, `counselor_id`, `title`, `note`, `follow_up_date`, `visible_to_parent` (`0|1`), timestamps.

### 9. Report Cards & Performance Ratings
- `report_cards`: `id`, `student_id`, `academic_year_id`, `semester_id` (nullable), `report_type` (`monthly|semester|annual`), `period_label`, `attendance_rate`, `homework_rate`, `final_average`, `academic_status`, `comments`, `is_published`, `generated_by`, timestamps.
- `report_card_items`: `id`, `report_card_id`, `subject_id`, `teacher_id`, `subject_grade`, `exam_average`, `homework_score`, `remarks`.
- `performance_scores`: `id`, `entity_type` (`student|teacher`), `entity_id`, `academic_year_id`, `period_label`, `academic_score`, `participation_score`, `homework_score`, `study_plan_score`, `attendance_score`, `improvement_score`, `total_score`, `category`, `breakdown_json`, `calculated_at`.

### 10. Notifications, CMS & Settings
- `notifications`: `id`, `user_id`, `type`, `title`, `body`, `link`, `is_read`, `read_at`, `created_at`.
- `announcements`: `id`, `author_id`, `target_role` (`all|teachers|students|parents|class`), `class_id` (nullable), `title`, `body`, `is_public`, `published_at`, timestamps.
- `events`: `id`, `title`, `slug`, `summary`, `body`, `event_date`, `location`, `cover_image`, `is_published`, `created_by`, timestamps.
- `media`: `id`, `title`, `category` (`banner|gallery|news|event`), `file_path`, `alt_text`, `uploaded_by`, `created_at`.
- `cms_posts`: `id`, `type` (`news|banner|activity`), `title`, `summary`, `body`, `image_path`, `link_url`, `sort_order`, `is_published`, `author_id`, timestamps.
- `settings`: `setting_key` (PK), `setting_value`, `setting_group`, `updated_at`.
- `migrations`: `id`, `migration` (unique), `applied_at`.
