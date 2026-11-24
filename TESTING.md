# Testing Guide — DarsYar (درسیار)

## 1. Automated Unit & Integration Suite

Run all automated tests against an isolated in-memory SQLite database via PHP 8.2+:

```bash
php bin/test.php
```

(On Windows with XAMPP: `P:\xampp\php\php.exe bin\test.php`)

### Test Coverage (`55` assertions across `8` groups)
1. **Authentication, CSRF & Rate Limiting**: Password hashing verification, wrong-password rejection, login rate limit lockout, CSRF verification/rejection, and `RoleMiddleware` 403 isolation.
2. **Anti-IDOR Isolation**: Strict parent-child linking (`parent_student`) and teacher class-subject assignment verification.
3. **Bulk Parent Account Generation**: Automated unique credential generation for unlinked students, duplicate skipping, idempotency on re-run, and forced first-login password rotation (`ForcePasswordChangeMiddleware`).
4. **Examinations & Daily Practice Tests**: Server-enforced start/end availability windows, MCQ auto-grading, negative marking, manual descriptive grading, parent notifications, offline exam scores, and daily practice drills.
5. **Homework, Study Plans & Attendance**: Submission/grading workflows, study plan completion percentages, and attendance persistence.
6. **Report Cards & Performance Rating Engine**: Automated calculation of weighted student and teacher ratings and class report card averages.
7. **Security Upload & Strictly Two-Language Parity**: Blocking `.php` uploads, enforcing `['fa', 'en']` only, verifying 100% key parity and zero empty values via `Translator::checkConsistency()`, rejecting/falling back `'ar'` -> `'fa'`, RTL/LTR directions, Jalali formatting, and localized Persian digits/percentages.
8. **DarsYar Extensions**: Multi-school `schools` table seeding (`SCH-001`), counselor assignments & counseling notes, study plan requests, and honest Skyroom connection diagnostics when API credentials are absent.

## 2. Live HTTP End-to-End Verification

With XAMPP MariaDB (`127.0.0.1:3306`) and the PHP server (`http://127.0.0.1:8000`) running, live HTTP tests validate public routes (`/`, `/news`, `/events`, `/login`), local webfonts (`Vazirmatn-var.woff2`, `Inter-var.woff2`), stylesheets/scripts/logo, route-preserving `/locale/en` and `/locale/fa` switching, `/api/theme` toggling, CSRF login/logout flows across all 7 role panels, RBAC 403 boundaries, and parent anti-IDOR isolation (`123` HTTP assertions).
