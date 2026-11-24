# Role-Based Access Control (RBAC) Matrix — DarsYar (درسیار)

All permissions are enforced server-side via middleware (`RoleMiddleware`, `PermissionMiddleware`) and domain ownership checks (`OwnershipGuard`).

| Capability / Permission | Super Admin | Director | Vice Principal | Counselor | Teacher | Student | Parent |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Manage Schools Registry | Yes | - | - | - | - | - | - |
| Manage Director & Admin Accounts | Yes | - | - | - | - | - | - |
| Global Settings, Language & Theme Defaults | Yes | - | - | - | - | - | - |
| Skyroom API Settings & Connection Test | Yes | - | - | - | - | - | - |
| Public CMS (Banners, News, Events, Gallery) | Yes | - | - | - | - | - | - |
| Audit Logs & System Health Diagnostics | Yes | - | - | - | - | - | - |
| Manage Vice Principals & School Announcements | Yes | Yes | - | - | - | - | - |
| Rating Weights & Teacher/Student Ratings | - | Yes | - | - | - | - | - |
| Class Comparisons, Reports & CSV Export | - | Yes | - | - | - | - | - |
| Upload/Manage Annual Timetables | - | Yes | Yes | - | - | - | - |
| Manage Academic Years, Semesters, Classes, Subjects | - | - | Yes | - | - | - | - |
| Manage Teachers, Students, Counselors, Parents | - | - | Yes | - | - | - | - |
| Bulk CSV Student Import & Class Reassignment | - | - | Yes | - | - | - | - |
| Bulk Parent Account Generation & Credential Export | - | - | Yes | - | - | - | - |
| School Attendance & Report Card Generation/Comments | - | - | Yes | - | - | - | - |
| Assign Students, Study Plans, Counseling Notes | - | - | - | Yes | - | - | - |
| View Student Performance Analysis & Plan Progress | - | Yes | - | Yes | - | Own | Linked Child |
| Request Weekly Study Plan & Toggle Own Tasks | - | - | - | - | - | Own | - |
| Assigned Classes, Subjects & Discussions | - | - | - | - | Assigned | Enrolled | - |
| Question Bank, Online & Offline Exams, Daily Tests | - | - | - | - | Assigned | Take Enrolled | View Child Results |
| Grade Exams & Homework | - | - | - | - | Assigned | - | - |
| Submit Homework | - | - | - | - | - | Enrolled | - |
| Schedule/Manage Skyroom Sessions | - | - | - | - | Assigned | Join Enrolled | View Participation |
| View Counseling Notes | - | - | - | Yes | - | - | Parent-Visible Only |
| View Report Cards (Printable PDF) | - | Yes | Yes | - | - | Own Published | Linked Child Published |
| Notifications | Own | Own | Own | Own | Own | Own | Own |

## Ownership Rules (Anti-IDOR)

- **Parent**: Every student-scoped query verifies `(parent_id, student_id)` exists in `parent_student` (`ParentController::resolveChild` returns HTTP 403 for unlinked `student_id`).
- **Teacher**: Every class/subject/exam/homework/classroom operation verifies `teacher_assignments` for `(teacher_id, class_id, subject_id)`.
- **Student**: Every attempt, submission, study task, report card, timetable file download, and attendance lookup restricts strictly to the authenticated `student_id` and `class_id`.
- **File Downloads (`/files/{category}/{id}`)**: Enforces ownership/class enrollment checks across `homework`, `submission`, `timetable`, and `exam` practice sheets.
