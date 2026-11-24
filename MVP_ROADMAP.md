# MVP Roadmap & Task Checklist — DarsYar (درسیار)

Status Legend: `[x]` Completed | `[-]` In Progress | `[!]` Requires External Credentials | `[ ]` Not Started

## Phase 0 — Core Foundation & Bilingual Enforcement
- [x] Verify PHP 8.2.12 + MariaDB 10.4.32 environment on XAMPP
- [x] Define cPanel & XAMPP-compatible Modular PHP MVC + Service architecture
- [x] Build Core framework (`App`, `Router`, `Request`, `Response`, `Database`, `Session`, `Csrf`, `Validator`, `Translator`, `DateHelper`, `Uploader`, `RateLimiter`, `View`, `Icon`, `Ui`)
- [x] Strictly enforce **two languages only**: Persian (`fa`, primary & default, RTL, Jalali dates, Persian digits) + English (`en`, secondary, LTR); removed Arabic locale files and selectors
- [x] Implement automated translation consistency checker (`Translator::checkConsistency()`) across all 681 translation keys
- [x] Author [`DESIGN.md`](DESIGN.md) and implement full CSS/JS design system with local `Vazirmatn` & `Inter` webfonts, Lucide inline SVG icons, and Light/Dark themes

## Phase 1 — Authentication, RBAC & Seven Panels
- [x] Authentication module (Argon2id/bcrypt hashing, login rate limiter, session regeneration, forced password change)
- [x] RBAC middleware & Anti-IDOR `OwnershipGuard` (including download ACL checks on `/files/*`)
- [x] 8 database migrations (`001`–`008`, 55 tables) + MySQL schema export (`database/schema_mysql.sql`) + idempotent seeder (`bin/seed.php`)
- [x] Complete dashboards & sub-pages for all 7 roles (`super_admin`, `director`, `vice_principal`, `counselor`, `teacher`, `student`, `parent`)

## Phase 2 — School Operations & Platform Administration
- [x] Super Admin multi-school management (`schools`), administrator management, CMS, events, announcements, audit logs, and system health diagnostics
- [x] Vice Principal academic structure CRUD, bulk CSV student import, class reassignment, and **Automated Bulk Parent Account Generation** + sibling linking + CSV/printable export
- [x] Director vice-principal oversight, analytical reports (with CSV export), announcements, and annual timetables

## Phase 3 — Exams, Daily Tests, Homework & Counseling
- [x] Teacher Question Bank CRUD, online exams (server windows, timers, negative marking, auto MCQ + manual descriptive grading), offline exams + CSV score import, daily practice tests
- [x] Homework assignments, student uploads, grading/feedback (`accepted` / `needs_revision`), and subject discussion groups
- [x] Counselor caseload assignments, study-plan requests, weekly study plans & daily tasks, parent-visible counseling notes, and student AJAX completion toggles

## Phase 4 — Online Classrooms, Parent Portal, Ratings & Local Execution
- [x] Skyroom JSON-RPC API integration layer + connection diagnostic endpoint (`POST /admin/settings/test-skyroom`) + fallback classroom URLs
- [x] Multi-child Parent portal with strict `?student_id=` ownership enforcement & printable monthly/semester/annual report cards
- [x] Configurable Teacher & Student Performance Rating Engine
- [x] Windows XAMPP launcher scripts (`START_DARSYAR.bat` / `STOP_DARSYAR.bat`) preserving existing database state
- [x] Automated test suite (`bin/test.php`: 55 assertions passing) + live HTTP E2E verification across all 7 panels (123 assertions passing)
- [!] Remote Skyroom room provisioning requires valid live Skyroom API credentials entered in Super Admin -> Settings -> Skyroom
