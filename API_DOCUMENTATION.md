# API & Route Documentation — DarsYar (درسیار)

**DarsYar** uses session-authenticated server routes plus Fetch/JSON endpoints protected by CSRF (`X-CSRF-TOKEN` or `_csrf` form field), strict RBAC middleware, and anti-IDOR ownership guards.

## Public Routes
- `GET /` — Public school homepage (CMS banners, school intro, news, events, gallery, contact).
- `GET /news` — Public news listing.
- `GET /events` — Public events listing.
- `GET /media/{id}` — Serve uploaded public media safely.
- `GET /login` / `POST /login` — Session login with rate limiting.
- `GET /register` / `POST /register` — Animated self-registration (`student` or `parent` roles only).
- `POST /logout` — Session logout.
- `GET /locale/{lang}` — Switch UI language (`fa` primary/default or `en` secondary only; preserves current route & query via `?redirect=` or same-host `Referer`).
- `POST /api/theme` — Persist Light/Dark theme preference (`light` | `dark`).

## Authenticated Shared Routes
- `GET /dashboard` — Redirects to the authenticated user's role panel.
- `GET /account/password` / `POST /account/password` — Change password (enforced on first login when `must_change_password = 1`).
- `GET /notifications` / `POST /notifications/read-all` — Notification center.
- `POST /api/notifications/{id}/read` — Mark notification as read.
- `GET /discussions` / `POST /discussions/create` / `POST /discussions/{id}/messages` — Role-scoped subject discussions (`teacher`, `student`).
- `GET /report-cards/{id}/print` — Printable report card view (authorized staff, owning student, linked parent).
- `GET /files/{category}/{id}` — Ownership-verified attachment download (`homework`, `submission`, `timetable`, `exam`).

## Panel Route Prefixes
- `/admin/*` — Super Admin panel (`/admin/dashboard`, `/admin/schools`, `/admin/admins`, `/admin/cms`, `/admin/announcements`, `/admin/events`, `/admin/settings`, `/admin/settings/test-skyroom`, `/admin/audit-logs`, `/admin/health`).
- `/director/*` — School Director panel (`/director/dashboard`, `/director/vice-principals`, `/director/teacher-ratings`, `/director/student-ratings`, `/director/class-comparison`, `/director/reports`, `/director/reports/export-csv`, `/director/announcements`, `/director/timetables`).
- `/vice-principal/*` — Vice Principal panel (`/vice-principal/dashboard`, `/vice-principal/academic`, `/vice-principal/staff`, `/vice-principal/students`, `/vice-principal/students/sample-csv`, `/vice-principal/parents`, `/vice-principal/parents/generate-bulk`, `/vice-principal/parents/export-csv`, `/vice-principal/parents/print`, `/vice-principal/timetables`, `/vice-principal/attendance`, `/vice-principal/report-cards`).
- `/counselor/*` — Educational Counselor panel (`/counselor/dashboard`, `/counselor/study-plans`, `/counselor/notes`, `/counselor/students/{id}`).
- `/teacher/*` — Teacher panel (`/teacher/dashboard`, `/teacher/classes`, `/teacher/questions`, `/teacher/exams`, `/teacher/exams/{id}`, `/teacher/exams/attempt/{id}/grade`, `/teacher/daily-tests`, `/teacher/homework`, `/teacher/classrooms`, `/teacher/attendance`).
- `/student/*` — Student panel (`/student/dashboard`, `/student/schedule`, `/student/exams`, `/student/exams/{id}/take`, `/student/exams/{id}/result`, `/student/daily-tests`, `/student/homework`, `/student/study-plans`, `POST /api/student/study-tasks/{id}/toggle`, `/student/classrooms`, `/student/attendance`, `/student/report-cards`).
- `/parent/*` — Parent panel (`/parent/dashboard`, `/parent/exams`, `/parent/homework`, `/parent/study-plans`, `/parent/attendance`, `/parent/report-cards`, `/parent/timetables` with `?student_id=...` anti-IDOR verification).
