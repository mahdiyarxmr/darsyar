<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Config;
use App\Core\Database;
use App\Modules\Auth\AuthService;

final class DatabaseSeeder
{
    public static function run(): void
    {
        $now = date('Y-m-d H:i:s');

        Database::transaction(static function () use ($now): void {
            // 1. Roles & Permissions
            $rolesConfig = Config::get('permissions.roles', []);
            $roleIds = [];
            foreach ($rolesConfig as $slug => $meta) {
                $existing = Database::fetch('SELECT id FROM roles WHERE slug = ?', [$slug]);
                if ($existing) {
                    $roleIds[$slug] = (int) $existing['id'];
                } else {
                    $roleIds[$slug] = Database::insert(
                        'INSERT INTO roles (slug, name_key, created_at) VALUES (?, ?, ?)',
                        [$slug, $meta['name_key'], $now]
                    );
                }

                foreach ($meta['permissions'] as $permSlug) {
                    $perm = Database::fetch('SELECT id FROM permissions WHERE slug = ?', [$permSlug]);
                    if (!$perm) {
                        $module = explode('.', $permSlug)[0];
                        $permId = Database::insert(
                            'INSERT INTO permissions (slug, module, description) VALUES (?, ?, ?)',
                            [$permSlug, $module, $permSlug]
                        );
                    } else {
                        $permId = (int) $perm['id'];
                    }

                    $linked = Database::fetchColumn(
                        'SELECT COUNT(*) FROM role_permissions WHERE role_id = ? AND permission_id = ?',
                        [$roleIds[$slug], $permId]
                    );
                    if ((int) $linked === 0) {
                        Database::execute(
                            'INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)',
                            [$roleIds[$slug], $permId]
                        );
                    }
                }
            }

            // 2. Default Settings (DarsYar Persian-first defaults + Rating Weights + Skyroom)
            $defaultSettings = [
                'school_name' => ['دبیرستان هوشمند درسیار', 'school'],
                'school_tagline' => ['پلتفرم جامع مدیریت مدرسه و آموزش هوشمند', 'school'],
                'school_intro' => ['درسیار سامانه‌ای یکپارچه برای مدیریت آموزشی، آزمون‌های آنلاین و حضوری، تکالیف، برنامه‌ریزی مشاوره‌ای و ارتباط شفاف با اولیا است.', 'school'],
                'school_phone' => ['021-88001234', 'school'],
                'school_email' => ['info@darsyar.school', 'school'],
                'school_address' => ['تهران، بلوار دانش، پلاک ۱۲۰', 'school'],
                'hero_title' => ['به سامانه آموزشی درسیار خوش آمدید', 'cms'],
                'hero_subtitle' => ['مدیریت یکپارچه آزمون‌ها، تکالیف، برنامه مطالعاتی، کلاس‌های آنلاین اسکای‌روم و کارنامه‌های هوشمند', 'cms'],
                'default_locale' => ['fa', 'general'],
                'default_theme' => ['light', 'general'],
                'student_weight_academic' => ['35', 'ratings'],
                'student_weight_homework' => ['20', 'ratings'],
                'student_weight_daily_tests' => ['15', 'ratings'],
                'student_weight_study_plan' => ['15', 'ratings'],
                'student_weight_attendance' => ['10', 'ratings'],
                'student_weight_improvement' => ['5', 'ratings'],
                'teacher_weight_homework' => ['25', 'ratings'],
                'teacher_weight_exams' => ['25', 'ratings'],
                'teacher_weight_improvement' => ['25', 'ratings'],
                'teacher_weight_attendance' => ['15', 'ratings'],
                'teacher_weight_activity' => ['10', 'ratings'],
                'skyroom_enabled' => ['0', 'skyroom'],
                'skyroom_api_url' => ['https://www.skyroom.online/skyroom/api/', 'skyroom'],
                'skyroom_api_key' => ['', 'skyroom'],
            ];

            foreach ($defaultSettings as $key => [$val, $group]) {
                $exists = Database::fetch('SELECT setting_key, setting_value FROM settings WHERE setting_key = ?', [$key]);
                if (!$exists) {
                    Database::execute(
                        'INSERT INTO settings (setting_key, setting_value, setting_group, updated_at) VALUES (?, ?, ?, ?)',
                        [$key, $val, $group, $now]
                    );
                } elseif (str_contains((string) ($exists['setting_value'] ?? ''), '???')) {
                    Database::execute(
                        'UPDATE settings SET setting_value = ?, updated_at = ? WHERE setting_key = ?',
                        [$val, $now, $key]
                    );
                }
            }

            // 3. Academic Year, Semester, Grades, Classes, Subjects
            $year = Database::fetch('SELECT id FROM academic_years WHERE name = ?', ['1404-1405']);
            if (!$year) {
                $year = Database::fetch('SELECT id FROM academic_years WHERE is_active = 1 LIMIT 1');
            }
            $yearId = $year ? (int) $year['id'] : Database::insert(
                'INSERT INTO academic_years (name, start_date, end_date, is_active, created_at) VALUES (?, ?, ?, 1, ?)',
                ['1404-1405', '2025-09-23', '2026-06-21', $now]
            );

            $sem = Database::fetch('SELECT id FROM semesters WHERE academic_year_id = ? LIMIT 1', [$yearId]);
            if (!$sem) {
                Database::insert(
                    'INSERT INTO semesters (academic_year_id, name, start_date, end_date, is_active) VALUES (?, ?, ?, ?, 1)',
                    [$yearId, 'نیم‌سال اول', '2025-09-23', '2026-01-20']
                );
            }

            $grade10 = Database::fetch("SELECT id FROM grades WHERE level_order = 10 OR name = 'Grade 10' LIMIT 1");
            $grade10Id = $grade10 ? (int) $grade10['id'] : Database::insert(
                'INSERT INTO grades (name, level_order) VALUES (?, ?)',
                ['پایه دهم', 10]
            );
            $grade11 = Database::fetch("SELECT id FROM grades WHERE level_order = 11 OR name = 'Grade 11' LIMIT 1");
            $grade11Id = $grade11 ? (int) $grade11['id'] : Database::insert(
                'INSERT INTO grades (name, level_order) VALUES (?, ?)',
                ['پایه یازدهم', 11]
            );

            $class10A = Database::fetch('SELECT id FROM classes WHERE academic_year_id = ? AND name = ?', [$yearId, '10-A']);
            $class10AId = $class10A ? (int) $class10A['id'] : Database::insert(
                'INSERT INTO classes (academic_year_id, grade_id, name, capacity, room_number, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$yearId, $grade10Id, '10-A', 30, '۲۰۱', $now]
            );
            $class11A = Database::fetch('SELECT id FROM classes WHERE academic_year_id = ? AND name = ?', [$yearId, '11-A']);
            $class11AId = $class11A ? (int) $class11A['id'] : Database::insert(
                'INSERT INTO classes (academic_year_id, grade_id, name, capacity, room_number, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$yearId, $grade11Id, '11-A', 30, '۳۰۱', $now]
            );

            $subjectsData = [
                ['MATH101', 'ریاضیات', 'ریاضیات پایه دهم و یازدهم'],
                ['PHYS101', 'فیزیک', 'فیزیک و آزمایشگاه'],
                ['CHEM101', 'شیمی', 'شیمی پایه'],
                ['BIOL101', 'زیست‌شناسی', 'زیست‌شناسی علوم تجربی'],
                ['ENGL101', 'زبان انگلیسی', 'زبان و نگارش انگلیسی'],
            ];
            $subjectIds = [];
            foreach ($subjectsData as [$code, $name, $desc]) {
                $subj = Database::fetch('SELECT id FROM subjects WHERE code = ?', [$code]);
                $subjectIds[$code] = $subj ? (int) $subj['id'] : Database::insert(
                    'INSERT INTO subjects (code, name, description) VALUES (?, ?, ?)',
                    [$code, $name, $desc]
                );
                foreach ([$class10AId, $class11AId] as $cid) {
                    $csExists = Database::fetchColumn(
                        'SELECT COUNT(*) FROM class_subjects WHERE class_id = ? AND subject_id = ?',
                        [$cid, $subjectIds[$code]]
                    );
                    if ((int) $csExists === 0) {
                        Database::execute(
                            'INSERT INTO class_subjects (class_id, subject_id, weekly_hours) VALUES (?, ?, 4)',
                            [$cid, $subjectIds[$code]]
                        );
                    }
                }
            }

            // 4. Seed Demo Users across all 7 roles (idempotent; preserves existing rows)
            $passwordHash = AuthService::hashPassword('School@2026');

            $createUser = static function (string $username, string $email, string $fullName, string $roleSlug) use ($passwordHash, $roleIds, $now): int {
                $existing = Database::fetch('SELECT id FROM users WHERE username = ?', [$username]);
                if ($existing) {
                    return (int) $existing['id'];
                }
                $uid = Database::insert(
                    'INSERT INTO users (username, email, password_hash, full_name, locale, theme, status, must_change_password, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?)',
                    [$username, $email, $passwordHash, $fullName, 'fa', 'light', 'active', $now, $now]
                );
                Database::execute('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)', [$uid, $roleIds[$roleSlug]]);
                return $uid;
            };

            $superAdminUid = $createUser('superadmin', 'superadmin@darsyar.school', 'مدیر کل سامانه درسیار', 'super_admin');
            if (!Database::fetch('SELECT id FROM administrators WHERE user_id = ?', [$superAdminUid])) {
                Database::insert('INSERT INTO administrators (user_id, title, department) VALUES (?, ?, ?)', [$superAdminUid, 'مدیر کل سیستم', 'فناوری اطلاعات و راهبری']);
            }

            $directorUid = $createUser('director', 'director@darsyar.school', 'دکتر آریا کریمی', 'director');
            if (!Database::fetch('SELECT id FROM administrators WHERE user_id = ?', [$directorUid])) {
                Database::insert('INSERT INTO administrators (user_id, title, department) VALUES (?, ?, ?)', [$directorUid, 'مدیر مدرسه', 'مدیریت اجرایی']);
            }

            // Seed default school in `schools` table
            if ((int) Database::fetchColumn('SELECT COUNT(*) FROM schools') === 0) {
                Database::insert(
                    'INSERT INTO schools (code, name, city, address, phone, email, director_user_id, status, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    ['SCH-001', 'دبیرستان هوشمند درسیار', 'تهران', 'بلوار دانش، پلاک ۱۲۰', '021-88001234', 'info@darsyar.school', $directorUid, 'active', $now, $now]
                );
            }

            $vpUid = $createUser('viceprincipal', 'vp@darsyar.school', 'سارا محمدی', 'vice_principal');
            if (!Database::fetch('SELECT id FROM administrators WHERE user_id = ?', [$vpUid])) {
                Database::insert('INSERT INTO administrators (user_id, title, department) VALUES (?, ?, ?)', [$vpUid, 'معاون آموزشی', 'امور آموزشی']);
            }

            $counselorUid = $createUser('counselor', 'counselor@darsyar.school', 'رضا حسینی', 'counselor');
            $counselorRow = Database::fetch('SELECT id FROM counselors WHERE user_id = ?', [$counselorUid]);
            $counselorId = $counselorRow ? (int) $counselorRow['id'] : Database::insert(
                'INSERT INTO counselors (user_id, employee_code, specialty) VALUES (?, ?, ?)',
                [$counselorUid, 'CNS-101', 'برنامه‌ریزی تحصیلی و کنکور']
            );

            $teacherUid = $createUser('teacher1', 'teacher1@darsyar.school', 'استاد نیما رحیمی', 'teacher');
            $teacherRow = Database::fetch('SELECT id FROM teachers WHERE user_id = ?', [$teacherUid]);
            $teacherId = $teacherRow ? (int) $teacherRow['id'] : Database::insert(
                'INSERT INTO teachers (user_id, employee_code, specialization, hire_date) VALUES (?, ?, ?, ?)',
                [$teacherUid, 'TCH-201', 'ریاضیات و فیزیک', '2022-09-01']
            );

            foreach (['MATH101', 'PHYS101'] as $code) {
                $ta = Database::fetchColumn(
                    'SELECT COUNT(*) FROM teacher_assignments WHERE teacher_id = ? AND class_id = ? AND subject_id = ? AND academic_year_id = ?',
                    [$teacherId, $class10AId, $subjectIds[$code], $yearId]
                );
                if ((int) $ta === 0) {
                    Database::execute(
                        'INSERT INTO teacher_assignments (teacher_id, class_id, subject_id, academic_year_id, created_at) VALUES (?, ?, ?, ?, ?)',
                        [$teacherId, $class10AId, $subjectIds[$code], $yearId, $now]
                    );
                }
            }

            // Students: student1 + student4 linked to parent1 (demonstrating multi-child switching);
            // student2 (10-A) & student3 (11-A) unlinked initially for bulk parent generation demo.
            $student1Uid = $createUser('student1', 'student1@darsyar.school', 'علی احمدی', 'student');
            $student1Row = Database::fetch('SELECT id FROM students WHERE user_id = ?', [$student1Uid]);
            $student1Id = $student1Row ? (int) $student1Row['id'] : Database::insert(
                'INSERT INTO students (user_id, student_code, national_id, class_id, guardian_phone, enrollment_date) VALUES (?, ?, ?, ?, ?, ?)',
                [$student1Uid, 'STU-1001', '0011223344', $class10AId, '09120001001', '2025-09-23']
            );

            $student2Uid = $createUser('student2', 'student2@darsyar.school', 'مینا تهرانی', 'student');
            $student2Row = Database::fetch('SELECT id FROM students WHERE user_id = ?', [$student2Uid]);
            $student2Id = $student2Row ? (int) $student2Row['id'] : Database::insert(
                'INSERT INTO students (user_id, student_code, national_id, class_id, guardian_phone, enrollment_date) VALUES (?, ?, ?, ?, ?, ?)',
                [$student2Uid, 'STU-1002', '0011223345', $class10AId, '09120001002', '2025-09-23']
            );

            $student3Uid = $createUser('student3', 'student3@darsyar.school', 'کیان فرهادی', 'student');
            $student3Row = Database::fetch('SELECT id FROM students WHERE user_id = ?', [$student3Uid]);
            $student3Id = $student3Row ? (int) $student3Row['id'] : Database::insert(
                'INSERT INTO students (user_id, student_code, national_id, class_id, guardian_phone, enrollment_date) VALUES (?, ?, ?, ?, ?, ?)',
                [$student3Uid, 'STU-1003', '0011223346', $class11AId, '09120001003', '2025-09-23']
            );

            $student4Uid = $createUser('student4', 'student4@darsyar.school', 'سارا احمدی', 'student');
            $student4Row = Database::fetch('SELECT id FROM students WHERE user_id = ?', [$student4Uid]);
            $student4Id = $student4Row ? (int) $student4Row['id'] : Database::insert(
                'INSERT INTO students (user_id, student_code, national_id, class_id, guardian_phone, enrollment_date) VALUES (?, ?, ?, ?, ?, ?)',
                [$student4Uid, 'STU-1004', '0011223347', $class11AId, '09120001001', '2025-09-23']
            );

            $parent1Uid = $createUser('parent1', 'parent1@darsyar.school', 'حسن احمدی', 'parent');
            $parent1Row = Database::fetch('SELECT id FROM parents WHERE user_id = ?', [$parent1Uid]);
            $parent1Id = $parent1Row ? (int) $parent1Row['id'] : Database::insert(
                'INSERT INTO parents (user_id, national_id, occupation, emergency_phone) VALUES (?, ?, ?, ?)',
                [$parent1Uid, '0099887766', 'مهندس عمران', '09120001001']
            );

            foreach ([$student1Id, $student4Id] as $idx => $childSid) {
                $linkExists = Database::fetchColumn(
                    'SELECT COUNT(*) FROM parent_student WHERE parent_id = ? AND student_id = ?',
                    [$parent1Id, $childSid]
                );
                if ((int) $linkExists === 0) {
                    Database::execute(
                        'INSERT INTO parent_student (parent_id, student_id, relationship, is_primary, created_at) VALUES (?, ?, ?, ?, ?)',
                        [$parent1Id, $childSid, 'father', $idx === 0 ? 1 : 0, $now]
                    );
                }
            }

            // Counselor assignments & sample counseling note
            foreach ([$student1Id, $student2Id, $student3Id, $student4Id] as $sid) {
                $caExists = (int) Database::fetchColumn(
                    'SELECT COUNT(*) FROM counselor_assignments WHERE counselor_id = ? AND student_id = ?',
                    [$counselorId, $sid]
                );
                if ($caExists === 0) {
                    Database::execute(
                        'INSERT INTO counselor_assignments (counselor_id, student_id, assigned_at) VALUES (?, ?, ?)',
                        [$counselorId, $sid, $now]
                    );
                }
            }

            if ((int) Database::fetchColumn('SELECT COUNT(*) FROM counseling_notes') === 0) {
                Database::insert(
                    'INSERT INTO counseling_notes (student_id, counselor_id, title, note, follow_up_date, visible_to_parent, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, 1, ?, ?)',
                    [
                        $student1Id,
                        $counselorId,
                        'جلسه ارزیابی پیشرفت هفتگی',
                        'عملکرد دانش‌آموز در درس ریاضیات بسیار مطلوب است. پیشنهاد می‌شود زمان مرور روزانه درس فیزیک ۲۰ دقیقه افزایش یابد.',
                        date('Y-m-d', strtotime('+7 days')),
                        $now,
                        $now,
                    ]
                );
            }

            // Sample online classroom & discussion group
            if ((int) Database::fetchColumn('SELECT COUNT(*) FROM online_class_sessions') === 0) {
                Database::insert(
                    'INSERT INTO online_class_sessions (teacher_id, class_id, subject_id, title, description, scheduled_start, scheduled_end, provider, fallback_url, status, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $teacherId,
                        $class10AId,
                        $subjectIds['MATH101'],
                        'کلاس حل تمرین ریاضیات — مبحث تابع',
                        'مرور تمرین‌های فصل تابع و آمادگی آزمون میان‌ترم',
                        date('Y-m-d H:i:s', strtotime('+1 day 16:00')),
                        date('Y-m-d H:i:s', strtotime('+1 day 17:15')),
                        'external',
                        'https://www.skyroom.online',
                        'scheduled',
                        $now,
                        $now,
                    ]
                );
            }

            if ((int) Database::fetchColumn('SELECT COUNT(*) FROM discussion_groups') === 0) {
                $gid = Database::insert(
                    'INSERT INTO discussion_groups (class_id, subject_id, teacher_id, title, description, created_at)
                     VALUES (?, ?, ?, ?, ?, ?)',
                    [$class10AId, $subjectIds['MATH101'], $teacherId, 'پرسش و پاسخ ریاضی ۱۰-A', 'گروه رفع اشکال درسی و تمرین‌های هفتگی', $now]
                );
                Database::insert(
                    'INSERT INTO discussion_messages (group_id, user_id, message, created_at) VALUES (?, ?, ?, ?)',
                    [$gid, $teacherUid, 'سلام دانش‌آموزان عزیز؛ سوالات فصل اول را می‌توانید در این گروه مطرح کنید.', $now]
                );
            }

            // 5. Initial Public Announcements, News & Events
            if ((int) Database::fetchColumn('SELECT COUNT(*) FROM announcements') === 0) {
                Database::execute(
                    'INSERT INTO announcements (author_id, target_role, title, body, is_public, published_at, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, ?, ?)',
                    [$superAdminUid, 'all', 'آغاز به کار پورتال هوشمند درسیار در سال تحصیلی جدید', 'تمامی دبیران، دانش‌آموزان، مشاوران و اولیا می‌توانند برنامه هفتگی، آزمون‌ها، تکالیف و کارنامه‌ها را به صورت آنلاین پیگیری کنند.', $now, $now, $now]
                );
            }

            if ((int) Database::fetchColumn('SELECT COUNT(*) FROM events') === 0) {
                Database::execute(
                    'INSERT INTO events (title, slug, summary, body, event_date, location, is_published, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?)',
                    ['المپیاد سالانه علوم و رباتیک دانش‌آموزی', 'annual-stem-robotics-olympiad', 'ارائه پروژه‌های فیزیک، برنامه‌نویسی و ریاضی توسط تیم‌های دانش‌آموزی', 'نمایشگاه دستاوردهای علمی دانش‌آموزان با حضور اولیا و دبیران در سالن همایش مدرسه برگزار می‌شود.', date('Y-m-d', strtotime('+14 days')), 'سالن همایش مرکزی مدرسه', $superAdminUid, $now, $now]
                );
            }

            if ((int) Database::fetchColumn('SELECT COUNT(*) FROM cms_posts') === 0) {
                Database::execute(
                    'INSERT INTO cms_posts (type, title, summary, body, sort_order, is_published, author_id, created_at, updated_at) VALUES (?, ?, ?, ?, 1, 1, ?, ?, ?)',
                    ['news', 'راه‌اندازی سامانه یکپارچه آزمون و مشاوره درسیار', 'آزمون‌های هوشمند و برنامه‌ریزی مطالعاتی هفتگی در پلتفرم درسیار فعال شد.', 'درسیار یادگیری کلاسی، اطلاع‌رسانی خودکار اولیا و برنامه‌ریزی مشاوره‌ای را در یک محیط ساده و امن یکپارچه می‌کند.', $superAdminUid, $now, $now]
                );
                Database::execute(
                    'INSERT INTO cms_posts (type, title, summary, body, sort_order, is_published, author_id, created_at, updated_at) VALUES (?, ?, ?, ?, 1, 1, ?, ?, ?)',
                    ['activity', 'تمرین‌های روزانه تسلط بر دروس تخصصی', 'تقویت پیوستگی مطالعه با تست‌های روزانه ۱۰ دقیقه‌ای ریاضی و علوم', 'آزمونک‌های تمرینی روزانه پیشرفت هر درس را مستقل از نمرات رسمی ثبت و تحلیل می‌کنند.', $superAdminUid, $now, $now]
                );
            }
        });
    }
}
