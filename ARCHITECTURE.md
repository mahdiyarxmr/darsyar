# System Architecture — DarsYar (درسیار)

## Architectural Style

**DarsYar** uses a **Modular PHP 8.2+ MVC + Service Layer Architecture** following PSR-4 autoloading:

- **Front Controller (`public/index.php`)**: Single HTTP entry point handling environment bootstrap, global settings hydration (`default_locale`, `default_theme`), localization, session initialization, routing, middleware execution, and response dispatch.
- **Core Infrastructure (`app/Core/`)**:
  - `App`, `Config`, `Router`, `Request`, `Response`
  - `Database`: PDO singleton with prepared statement helpers and transaction wrappers (`transaction()`) compatible with MySQL/MariaDB and SQLite.
  - `Session` (`darsyar_sid`), `Csrf`, `Validator`, `RateLimiter`, `Translator` (`fa` & `en` only + `checkConsistency()`), `DateHelper` (Jalali conversion, Persian digit localization, Gregorian fallback), `Uploader`, `View`, `Icon` (Lucide SVG registry), `Ui` (breadcrumbs, empty states, pagination, localized status/rating helpers).
- **Middleware (`app/Middleware/`)**:
  - `AuthMiddleware`: Verifies active session and active user status.
  - `ForcePasswordChangeMiddleware`: Enforces password rotation on accounts flagged `must_change_password = 1` (e.g., auto-generated parent accounts).
  - `RoleMiddleware` & `PermissionMiddleware`: Enforces RBAC before controller execution.
- **Domain Modules (`app/Modules/`)**:
  - Separated by domain: `Auth`, `Authorization`, `Users`, `Students`, `Parents`, `Teachers`, `Counselors`, `Administrators`, `Academic`, `Exams`, `Questions`, `Homework`, `Counseling`, `Attendance`, `Classrooms`, `Discussions`, `Reports`, `Notifications`, `CMS`, `Settings`, `AuditLogs`.
  - Service classes encapsulate business logic (e.g., `BulkParentGeneratorService`, `ExamService`, `HomeworkService`, `CounselingService`, `DailyTestService`, `PerformanceRatingService`, `ReportCardService`, `NotificationService`, `SkyroomService`).
- **Presentation Layer (`app/Views/`)**:
  - Layouts (`public.php`, `main.php`, `print.php`) and modular partials (`sidebar_nav.php`, `parent_child_switcher.php`) styled via `public/assets/css/app.css` and `public/assets/js/app.js` using logical CSS properties and local `Vazirmatn`/`Inter` webfonts per [`DESIGN.md`](DESIGN.md).
  - Server-rendered views enhanced with progressive Fetch API endpoints for theme persistence (`/api/theme`), study task toggles (`/api/student/study-tasks/{id}/toggle`), notification read states, modals, and confirmation dialogs.
