# System Requirements — DarsYar (درسیار)

## Functional Requirements

- **FR-01**: Seven distinct role-based panels (`super_admin`, `director`, `vice_principal`, `counselor`, `teacher`, `student`, `parent`) backed by one normalized database.
- **FR-02**: Super Admin platform administration: schools registry, director/admin accounts, public website CMS (banners, news, activities, media), announcements, events, system settings (default language, default theme, Skyroom credentials + live connection test), audit logs, and system health diagnostics.
- **FR-03**: Director oversight of vice principals, configurable teacher & student performance ratings, class comparisons, analytical reports (with CSV export), announcements, and annual timetables.
- **FR-04**: Vice Principal operations: academic structure CRUD (years, semesters, grades, classes, subjects, teacher assignments), staff & student management (including class reassignment and CSV import), automated bulk parent generation + sibling linking + CSV/printable export, daily attendance, and monthly/semester/annual report cards with printable PDF view and editorial comments.
- **FR-05**: Counselor workflow: student caseload assignment, study-plan request fulfillment, weekly study plans & daily study tasks, student completion analytics, student performance drill-down, and parent-toggleable counseling notes.
- **FR-06**: Teacher instruction: assigned classes/subjects, question bank, online/offline exams, MCQ & descriptive grading, daily practice tests, homework creation & grading, Skyroom online classrooms, class attendance, and subject discussion groups.
- **FR-07**: Server-side enforcement of online exam availability windows (`starts_at` to `ends_at`), duration timers, and attempt limits.
- **FR-08**: Student workspace: personal dashboard, class timetable & subject teachers, exam participation & published results, daily practice drills, homework submission & feedback, interactive weekly study plans + plan requests, Skyroom classrooms, attendance, notifications, and report cards.
- **FR-09**: Parent portal restricted strictly to linked children (`parent_student`) with instant child switching, attendance, exam & daily test overview, homework status, study-plan progress, visible counseling notes, timetables, and printable report cards.
- **FR-10**: Strictly two languages (`fa` primary/default RTL with Jalali dates & Persian digits; `en` secondary LTR with Gregorian dates) preserving current route and state during language switches, plus Light/Dark themes built per [`DESIGN.md`](DESIGN.md).

## Non-Functional Requirements

- **NFR-01 (Hosting)**: Runs natively on XAMPP (local) and standard cPanel Apache + PHP 8.2+ + MySQL 8+ / MariaDB 10.4+ without Node.js, Docker, Redis, or external daemon dependencies.
- **NFR-02 (Security)**: Argon2id/bcrypt password hashing, session regeneration on login, CSRF verification on all state-changing routes/APIs, prepared PDO statements, HTML output escaping, strict MIME/size file upload validation, login rate limiting, audit logging, and strict anti-IDOR ownership guards.
- **NFR-03 (Maintainability & Localization)**: Modular domain directories (`app/Modules/*`), locally hosted `Vazirmatn` & `Inter` typography, Lucide inline SVG icons (`App\Core\Icon`), 100% translation key parity between `lang/fa.php` and `lang/en.php` enforced via `Translator::checkConsistency()`, and automated integration + live HTTP verification.
