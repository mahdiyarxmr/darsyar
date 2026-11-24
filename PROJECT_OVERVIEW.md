# Project Overview — درسیار (DarsYar)

## Vision & Goals

**DarsYar (درسیار)** unifies school administration, classroom instruction, online/offline examinations, daily subject mastery drills, homework workflows, individualized educational counseling, and transparent parent communication inside a single normalized relational database and modular PHP 8.2+ web platform deployable cleanly on local XAMPP and standard cPanel shared hosting.

## Core Product Pillars

1. **Centralized School & Multi-Panel Operations**: Multi-school registry, academic years, semesters, grades, classes, subjects, timetables, and school/classroom attendance.
2. **Automated Onboarding & Parent Access**: Bulk CSV student import and one-click bulk parent account generation with unique usernames, temporary passwords, duplicate prevention, sibling linking, printable/CSV credential export, and mandatory password change on first login.
3. **Comprehensive Assessment Engine**:
   - **Online Examinations**: Server-enforced start/end availability windows, duration limits, question/option randomization, optional negative marking, automatic objective grading, and manual descriptive grading.
   - **Offline Examinations**: Physical exam score entry, CSV bulk import, downloadable practice sheets, and unified reporting.
   - **Daily Practice Tests**: Self-paced subject drills separate from official grades with accuracy and historical progress tracking.
4. **Homework & Study Counseling**: Attachment-enabled homework submissions and teacher feedback paired with counselor weekly study plans, student completion toggles, study-plan requests, and parent-visible counseling notes.
5. **Online Classrooms (Skyroom)**: Clean provider integration layer for the Skyroom JSON-RPC API, connection diagnostics in Super Admin settings, fallback classroom links, and participant attendance logs.
6. **Automated Cross-Panel Workflows**: Exam grading, homework grading, attendance recording, study plans, and published report cards automatically update dashboards and send role-scoped notifications.
7. **Configurable Performance Analytics**: Weighted teacher and student performance rating formulas adjustable by the School Director.
8. **Strictly Bilingual & Themeable UX**: Persian (`fa`, **PRIMARY and DEFAULT**, `dir="rtl"`, Jalali calendar, localized Persian digits) and English (`en`, SECONDARY, `dir="ltr"`, Gregorian calendar), with persistent Light and Dark themes styled strictly according to [`DESIGN.md`](DESIGN.md).
