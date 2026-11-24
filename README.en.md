🌐 **Language:** [فارسی (Persian)](README.md) | **English**

# DarsYar (درسیار) — School Management & Smart Educational Platform

**DarsYar** is a complete, modular, bilingual (**Persian primary/default RTL** + **English secondary LTR**) school management and digital learning platform built with **PHP 8.2+** and **MySQL / MariaDB**. Engineered without Node.js or Docker runtime dependencies, it runs directly on local **XAMPP** and standard **cPanel** shared hosting.

---

## Key Capabilities

### 1. Seven Independent Role-Based Panels (`RBAC` + Strict `Anti-IDOR`)
- **Super Administrator (`super_admin`)**: Platform overview, multi-school registry (`schools`), director & admin management, public website CMS (banners, news, activities, gallery, events), announcements, global language/theme/Skyroom settings with live connection probe, audit logs, and system health diagnostics.
- **School Director (`director`)**: Vice principal oversight, configurable teacher & student performance rating weights, class comparisons, analytical reports with CSV export, announcements, and annual timetables.
- **Vice Principal (`vice_principal`)**: Academic years, semesters, grades, classes, subjects, teacher assignments, staff & student management (class reassignment + CSV import), **Automated Bulk Parent Account Generation** (unique usernames, temporary passwords, duplicate prevention, sibling linking, CSV/printable exports, forced first-login password rotation), school attendance, and report card generation & editorial comments.
- **Educational Counselor (`counselor`)**: Assigned student caseload, study-plan request fulfillment, individualized weekly study plans & daily tasks, parent-toggleable counseling notes (`counseling_notes`), and student performance analysis.
- **Teacher (`teacher`)**: Assigned classes & student lists, question bank, online & offline exams (server-enforced availability windows, timers, randomization, negative marking, auto MCQ + manual descriptive grading, CSV offline import), daily practice tests, homework assignments & grading, Skyroom online classrooms, classroom attendance, and subject discussion groups.
- **Student (`student`)**: Personal dashboard, timetable & subjects, exam participation & published results, daily practice tests with progress chart, homework submission & feedback, interactive weekly study plans (Fetch API completion toggles + plan requests), Skyroom classrooms, discussions, notifications, and printable report cards.
- **Parent / Family (`parent`)**: Instant child switching strictly verified against `parent_student` (HTTP `403` anti-IDOR protection), attendance summary, exam & daily test scores, homework feedback, counselor study-plan progress, visible counseling notes, class timetables, and printable report cards.

### 2. Strictly Bilingual & Bespoke DarsYar Design System
- **Only 2 Supported Languages**: **Persian (`fa`)** as primary default (`dir="rtl"`, Jalali calendar, localized Persian digits) and **English (`en`)** as secondary (`dir="ltr"`, Gregorian calendar). All Arabic locale files and selectors were removed, and unsupported locale requests safely fall back to `fa`.
- **100% Translation Parity**: All `702` keys in [`lang/fa.php`](lang/fa.php) and [`lang/en.php`](lang/en.php) are verified for complete key parity and non-empty values via [`Translator::checkConsistency()`](app/Core/Translator.php).
- **Custom Tactile Design System ([`DESIGN.md`](DESIGN.md))**: Inspired by Refined Neo-Brutalism with DarsYar's exclusive palette — Warm Cream Paper (`#FAF9F5`) & Obsidian Dark (`#0B0F17`), Electric Cobalt (`#2554FF`) + Persian Tile Teal (`#0EA5A4`), crisp `1.5px` borders, hard offset shadows with tactile press physics, locally hosted `Vazirmatn` & `Inter` webfonts, outline Lucide SVGs (`App\Core\Icon`), progressive action drawers/tabs, and an animated **Login & Self-Registration** flow (`/login` & `/register` supporting `student` and `parent` registration).

---

## Tech Stack

| Layer | Technology |
| :--- | :--- |
| **Backend** | PHP `8.2+`, Modular PSR-4 MVC + Service Layer, PDO prepared statements |
| **Database** | MySQL `8+` / MariaDB `10.4+` (`8` migrations, `55` tables) + SQLite for isolated automated tests |
| **Frontend** | Semantic HTML5, CSS3 custom properties & logical properties, Vanilla JS + Fetch API + inline SVG charts |
| **Security** | Argon2id / bcrypt hashing, CSRF verification, login rate limiting, anti-IDOR guards (panels & `/files/*`), strict MIME/extension upload validation |
| **Hosting** | Local XAMPP (`START_DARSYAR.bat`) and standard cPanel Apache (`mod_rewrite` via `.htaccess`) |

---

## Quick Start (Local Development)

1. Copy environment configuration and configure your MySQL/MariaDB credentials:
   ```bash
   cp .env.example .env
   ```
2. Run database migrations (non-destructive):
   ```bash
   php bin/migrate.php
   ```
3. Seed initial academic structure and development accounts:
   ```bash
   php bin/seed.php
   ```
4. Launch the local PHP built-in server:
   - On Windows (XAMPP automated launcher):
     ```bat
     START_DARSYAR.bat
     ```
     (Use `STOP_DARSYAR.bat` to stop only the DarsYar PHP server without affecting shared MySQL/Apache.)
   - Or manually from project root:
     ```bash
     php -S 127.0.0.1:8000 -t public
     ```
5. Open `http://127.0.0.1:8000/login` (or `http://127.0.0.1:8000/register`).
   > Demo development accounts are displayed inside a collapsible drawer on `/login` only when `APP_ENV=local` and are automatically hidden in production.

---

## Automated Tests

Run the automated SQLite unit & integration suite:

```bash
php bin/test.php
```

- Covers **61 assertions across 8 test groups**: authentication/CSRF/rate limiting, anti-IDOR isolation, bulk parent account generation, exam windows & grading, homework/counseling/attendance, report cards & performance ratings, secure upload validation & `fa`/`en` key parity, DarsYar extensions, and public student/parent self-registration with privilege-escalation protection.

---

## Documentation Index

- [`README.md`](README.md) — مستندات اصلی پروژه به زبان فارسی
- [`DESIGN.md`](DESIGN.md) — Exclusive DarsYar design system specification
- [`PROJECT_OVERVIEW.md`](PROJECT_OVERVIEW.md) — Product vision & core pillars
- [`REQUIREMENTS.md`](REQUIREMENTS.md) — Functional & non-functional requirements
- [`ARCHITECTURE.md`](ARCHITECTURE.md) — Modular MVC & service architecture
- [`DATABASE_SCHEMA.md`](DATABASE_SCHEMA.md) — Relational database schema (`55` tables)
- [`RBAC_MATRIX.md`](RBAC_MATRIX.md) — 7-role permission matrix & ownership guards
- [`API_DOCUMENTATION.md`](API_DOCUMENTATION.md) — Server routes & AJAX endpoints
- [`DEPLOYMENT_CPANEL.md`](DEPLOYMENT_CPANEL.md) — Local XAMPP & cPanel production guide
- [`TESTING.md`](TESTING.md) — Automated & live HTTP testing guide
- [`MVP_ROADMAP.md`](MVP_ROADMAP.md) — Implementation checklist
- [`CHANGELOG.md`](CHANGELOG.md) — Release history
