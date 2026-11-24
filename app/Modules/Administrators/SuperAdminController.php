<?php

declare(strict_types=1);

namespace App\Modules\Administrators;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Translator;
use App\Core\Uploader;
use App\Core\Validator;
use App\Modules\AuditLogs\AuditLogService;
use App\Modules\Auth\AuthService;
use App\Modules\Classrooms\SkyroomService;
use App\Modules\Notifications\NotificationService;
use App\Modules\Settings\SettingsService;

final class SuperAdminController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $stats = [
            'total_users' => (int) Database::fetchColumn('SELECT COUNT(*) FROM users'),
            'active_students' => (int) Database::fetchColumn('SELECT COUNT(*) FROM students'),
            'active_teachers' => (int) Database::fetchColumn('SELECT COUNT(*) FROM teachers'),
            'published_events' => (int) Database::fetchColumn('SELECT COUNT(*) FROM events WHERE is_published = 1'),
            'announcements' => (int) Database::fetchColumn('SELECT COUNT(*) FROM announcements'),
            'audit_logs' => (int) Database::fetchColumn('SELECT COUNT(*) FROM audit_logs'),
            'schools' => (int) Database::fetchColumn('SELECT COUNT(*) FROM schools'),
        ];
        $roleRows = Database::fetchAll(
            'SELECT r.slug, COUNT(ur.user_id) AS cnt
             FROM roles r
             LEFT JOIN user_roles ur ON ur.role_id = r.id
             GROUP BY r.id, r.slug
             ORDER BY r.id ASC'
        );
        $recentLogs = AuditLogService::recent(10);

        return $this->view('panels/super_admin/dashboard', [
            'title' => Translator::get('panel.super_admin'),
            'stats' => $stats,
            'roleRows' => $roleRows,
            'recentLogs' => $recentLogs,
            'skyroomConfigured' => SkyroomService::isConfigured(),
        ]);
    }

    public function schools(Request $request): Response
    {
        $schools = Database::fetchAll(
            'SELECT sc.*, u.full_name AS director_name, u.username AS director_username
             FROM schools sc
             LEFT JOIN users u ON u.id = sc.director_user_id
             ORDER BY sc.id DESC'
        );
        $directors = Database::fetchAll(
            "SELECT u.id, u.full_name, u.username
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id
             WHERE r.slug = 'director'
             ORDER BY u.full_name ASC"
        );

        return $this->view('panels/super_admin/schools', [
            'title' => Translator::get('menu.schools'),
            'schools' => $schools,
            'directors' => $directors,
        ]);
    }

    public function createSchool(Request $request): Response
    {
        $data = $request->allPost();
        $v = new Validator();
        if (!$v->validate($data, [
            'code' => 'required|min:2|max:40',
            'name' => 'required|min:2|max:180',
            'city' => 'nullable|max:100',
            'phone' => 'nullable|max:30',
            'email' => 'nullable|email|max:191',
            'address' => 'nullable|max:255',
        ])) {
            $this->flash('error', (string) $v->firstError());
            return $this->redirect('/admin/schools');
        }

        $code = strtoupper(trim((string) $data['code']));
        if (Database::fetch('SELECT id FROM schools WHERE code = ?', [$code])) {
            $this->flash('error', Translator::get('validation.in', ['field' => 'code']));
            return $this->redirect('/admin/schools');
        }

        $dirId = ((int) ($data['director_user_id'] ?? 0)) ?: null;
        $now = date('Y-m-d H:i:s');
        $schoolId = Database::insert(
            'INSERT INTO schools (code, name, city, address, phone, email, director_user_id, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $code,
                trim((string) $data['name']),
                trim((string) ($data['city'] ?? '')) ?: null,
                trim((string) ($data['address'] ?? '')) ?: null,
                trim((string) ($data['phone'] ?? '')) ?: null,
                trim((string) ($data['email'] ?? '')) ?: null,
                $dirId,
                'active',
                $now,
                $now,
            ]
        );

        $authUser = $this->authUser();
        AuditLogService::log((int) $authUser['id'], 'school.created', 'school', $schoolId);
        $this->flash('success', Translator::get('admin.school_created'));
        return $this->redirect('/admin/schools');
    }

    public function toggleSchoolStatus(Request $request, array $params): Response
    {
        $id = (int) ($params['id'] ?? 0);
        $school = Database::fetch('SELECT id, status FROM schools WHERE id = ?', [$id]);
        if ($school) {
            $next = $school['status'] === 'active' ? 'inactive' : 'active';
            Database::execute('UPDATE schools SET status = ?, updated_at = ? WHERE id = ?', [$next, date('Y-m-d H:i:s'), $id]);
            $authUser = $this->authUser();
            AuditLogService::log((int) $authUser['id'], 'school.status_toggled', 'school', $id, ['status' => $next]);
            $this->flash('success', Translator::get('admin.school_toggled'));
        }
        return $this->redirect('/admin/schools');
    }

    public function admins(Request $request): Response
    {
        $roleFilter = (string) $request->query('role', '');
        $sql = "SELECT u.*, r.slug AS role_slug, a.title AS admin_title, a.department
                FROM users u
                JOIN user_roles ur ON ur.user_id = u.id
                JOIN roles r ON r.id = ur.role_id
                LEFT JOIN administrators a ON a.user_id = u.id
                WHERE r.slug IN ('super_admin', 'director', 'vice_principal')";
        $params = [];
        if (in_array($roleFilter, ['super_admin', 'director', 'vice_principal'], true)) {
            $sql .= ' AND r.slug = ?';
            $params[] = $roleFilter;
        }
        $sql .= ' ORDER BY u.id DESC';
        $admins = Database::fetchAll($sql, $params);

        return $this->view('panels/super_admin/admins', [
            'title' => Translator::get('menu.admins'),
            'admins' => $admins,
            'roleFilter' => $roleFilter,
        ]);
    }

    public function createAdmin(Request $request): Response
    {
        $data = $request->allPost();
        $validator = new Validator();
        if (!$validator->validate($data, [
            'full_name' => 'required|min:2|max:150',
            'username' => 'required|min:3|max:80',
            'email' => 'nullable|email|max:191',
            'password' => 'required|min:8|max:128',
            'role' => 'required|in:super_admin,director,vice_principal',
            'title' => 'nullable|max:100',
            'department' => 'nullable|max:100',
        ])) {
            $this->flash('error', (string) $validator->firstError());
            return $this->redirect('/admin/admins');
        }

        $username = trim((string) $data['username']);
        $email = trim((string) ($data['email'] ?? '')) ?: null;

        if (Database::fetch('SELECT id FROM users WHERE username = ?', [$username])) {
            $this->flash('error', Translator::get('admin.username_exists'));
            return $this->redirect('/admin/admins');
        }
        if ($email && Database::fetch('SELECT id FROM users WHERE email = ?', [$email])) {
            $this->flash('error', Translator::get('admin.email_exists'));
            return $this->redirect('/admin/admins');
        }

        $authUser = $this->authUser();
        $now = date('Y-m-d H:i:s');

        $newId = Database::transaction(static function () use ($data, $username, $email, $now): int {
            $uid = Database::insert(
                'INSERT INTO users (username, email, password_hash, full_name, locale, theme, status, must_change_password, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?)',
                [
                    $username,
                    $email,
                    AuthService::hashPassword((string) $data['password']),
                    trim((string) $data['full_name']),
                    'fa',
                    'light',
                    'active',
                    $now,
                    $now,
                ]
            );
            $roleId = (int) Database::fetchColumn('SELECT id FROM roles WHERE slug = ?', [$data['role']]);
            Database::execute('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)', [$uid, $roleId]);
            Database::insert(
                'INSERT INTO administrators (user_id, title, department) VALUES (?, ?, ?)',
                [$uid, trim((string) ($data['title'] ?? '')), trim((string) ($data['department'] ?? ''))]
            );
            return $uid;
        });

        AuditLogService::log((int) $authUser['id'], 'admin.created', 'user', $newId, ['role' => $data['role']]);
        $this->flash('success', Translator::get('admin.admin_created'));
        return $this->redirect('/admin/admins');
    }

    public function toggleAdminStatus(Request $request, array $params): Response
    {
        $targetId = (int) ($params['id'] ?? 0);
        $authUser = $this->authUser();
        if ($targetId === (int) $authUser['id']) {
            $this->flash('error', Translator::get('admin.cannot_deactivate_self'));
            return $this->redirect('/admin/admins');
        }

        $target = Database::fetch('SELECT id, status FROM users WHERE id = ?', [$targetId]);
        if ($target) {
            $next = $target['status'] === 'active' ? 'inactive' : 'active';
            Database::execute('UPDATE users SET status = ?, updated_at = ? WHERE id = ?', [$next, date('Y-m-d H:i:s'), $targetId]);
            AuditLogService::log((int) $authUser['id'], 'admin.status_toggled', 'user', $targetId, ['status' => $next]);
            $this->flash('success', Translator::get('admin.status_updated'));
        }
        return $this->redirect('/admin/admins');
    }

    public function cms(Request $request): Response
    {
        $settings = SettingsService::all();
        $posts = Database::fetchAll('SELECT * FROM cms_posts ORDER BY id DESC');
        $media = Database::fetchAll('SELECT * FROM media ORDER BY id DESC');

        return $this->view('panels/super_admin/cms', [
            'title' => Translator::get('menu.cms'),
            'settings' => $settings,
            'posts' => $posts,
            'media' => $media,
        ]);
    }

    public function saveCmsPost(Request $request): Response
    {
        $data = $request->allPost();
        $validator = new Validator();
        if (!$validator->validate($data, [
            'type' => 'required|in:news,banner,activity',
            'title' => 'required|min:2|max:180',
            'summary' => 'nullable|max:400',
            'body' => 'nullable',
        ])) {
            $this->flash('error', (string) $validator->firstError());
            return $this->redirect('/admin/cms');
        }

        $authUser = $this->authUser();
        $now = date('Y-m-d H:i:s');
        $imageMediaUrl = null;

        $file = $request->file('image');
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            try {
                $relPath = Uploader::upload($file, 'cms', Uploader::ALLOWED_IMAGES_ONLY);
                $mediaId = Database::insert(
                    'INSERT INTO media (title, category, file_path, alt_text, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                    [trim((string) $data['title']), $data['type'] === 'banner' ? 'banner' : 'news', $relPath, trim((string) $data['title']), (int) $authUser['id'], $now]
                );
                $imageMediaUrl = '/media/' . $mediaId;
            } catch (\Throwable $e) {
                $this->flash('error', $e->getMessage());
                return $this->redirect('/admin/cms');
            }
        }

        $id = Database::insert(
            'INSERT INTO cms_posts (type, title, summary, body, image_path, link_url, sort_order, is_published, author_id, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['type'],
                trim((string) $data['title']),
                trim((string) ($data['summary'] ?? '')),
                trim((string) ($data['body'] ?? '')),
                $imageMediaUrl,
                trim((string) ($data['link_url'] ?? '')) ?: null,
                (int) ($data['sort_order'] ?? 1),
                isset($data['is_published']) ? 1 : 0,
                (int) $authUser['id'],
                $now,
                $now,
            ]
        );

        AuditLogService::log((int) $authUser['id'], 'cms.post_created', 'cms_post', $id);
        $this->flash('success', Translator::get('common.save'));
        return $this->redirect('/admin/cms');
    }

    public function uploadMedia(Request $request): Response
    {
        $file = $request->file('file');
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->flash('error', Translator::get('errors.upload_failed'));
            return $this->redirect('/admin/cms');
        }

        $authUser = $this->authUser();
        $category = (string) $request->post('category', 'gallery');
        if (!in_array($category, ['banner', 'gallery', 'news', 'event'], true)) {
            $category = 'gallery';
        }

        try {
            $relPath = Uploader::upload($file, 'cms', Uploader::ALLOWED_IMAGES_ONLY);
            $mediaId = Database::insert(
                'INSERT INTO media (title, category, file_path, alt_text, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                [
                    trim((string) $request->post('title', 'Gallery Image')) ?: 'Gallery Image',
                    $category,
                    $relPath,
                    trim((string) $request->post('alt_text', '')),
                    (int) $authUser['id'],
                    date('Y-m-d H:i:s'),
                ]
            );
            AuditLogService::log((int) $authUser['id'], 'media.uploaded', 'media', $mediaId);
            $this->flash('success', Translator::get('common.save'));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }

        return $this->redirect('/admin/cms');
    }

    public function deleteCmsPost(Request $request, array $params): Response
    {
        $id = (int) ($params['id'] ?? 0);
        Database::execute('DELETE FROM cms_posts WHERE id = ?', [$id]);
        $this->flash('success', Translator::get('common.delete'));
        return $this->redirect('/admin/cms');
    }

    public function announcements(Request $request): Response
    {
        $announcements = Database::fetchAll(
            'SELECT a.*, u.full_name AS author_name FROM announcements a LEFT JOIN users u ON u.id = a.author_id ORDER BY a.id DESC'
        );
        return $this->view('panels/super_admin/announcements', [
            'title' => Translator::get('menu.announcements'),
            'announcements' => $announcements,
        ]);
    }

    public function createAnnouncement(Request $request): Response
    {
        $data = $request->allPost();
        $validator = new Validator();
        if (!$validator->validate($data, [
            'title' => 'required|min:2|max:180',
            'body' => 'required|min:2',
            'target_role' => 'required|in:all,teachers,students,parents',
        ])) {
            $this->flash('error', (string) $validator->firstError());
            return $this->redirect('/admin/announcements');
        }

        $authUser = $this->authUser();
        $now = date('Y-m-d H:i:s');
        $isPublic = isset($data['is_public']) ? 1 : 0;

        $id = Database::insert(
            'INSERT INTO announcements (author_id, target_role, title, body, is_public, published_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [(int) $authUser['id'], $data['target_role'], trim((string) $data['title']), trim((string) $data['body']), $isPublic, $now, $now, $now]
        );

        $roleFilter = match ($data['target_role']) {
            'teachers' => "('teacher')",
            'students' => "('student')",
            'parents' => "('parent')",
            default => "('director','vice_principal','counselor','teacher','student','parent')",
        };
        $targetUsers = Database::fetchAll(
            "SELECT u.id FROM users u JOIN user_roles ur ON ur.user_id = u.id JOIN roles r ON r.id = ur.role_id WHERE u.status = 'active' AND r.slug IN {$roleFilter}"
        );
        foreach ($targetUsers as $u) {
            NotificationService::notifyUser((int) $u['id'], 'announcement', trim((string) $data['title']), mb_substr(trim((string) $data['body']), 0, 140), '/');
        }

        AuditLogService::log((int) $authUser['id'], 'announcement.created', 'announcement', $id);
        $this->flash('success', Translator::get('admin.announcement_published'));
        return $this->redirect('/admin/announcements');
    }

    public function deleteAnnouncement(Request $request, array $params): Response
    {
        $id = (int) ($params['id'] ?? 0);
        Database::execute('DELETE FROM announcements WHERE id = ?', [$id]);
        $this->flash('success', Translator::get('common.delete'));
        return $this->redirect('/admin/announcements');
    }

    public function events(Request $request): Response
    {
        $events = Database::fetchAll('SELECT * FROM events ORDER BY event_date DESC, id DESC');
        return $this->view('panels/super_admin/events', [
            'title' => Translator::get('menu.events'),
            'events' => $events,
        ]);
    }

    public function createEvent(Request $request): Response
    {
        $data = $request->allPost();
        $validator = new Validator();
        if (!$validator->validate($data, [
            'title' => 'required|min:2|max:180',
            'event_date' => 'required',
            'location' => 'nullable|max:150',
            'summary' => 'nullable|max:400',
        ])) {
            $this->flash('error', (string) $validator->firstError());
            return $this->redirect('/admin/events');
        }

        $authUser = $this->authUser();
        $now = date('Y-m-d H:i:s');
        $coverPath = null;

        $file = $request->file('cover_image');
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            try {
                $relPath = Uploader::upload($file, 'cms', Uploader::ALLOWED_IMAGES_ONLY);
                $mediaId = Database::insert(
                    'INSERT INTO media (title, category, file_path, alt_text, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                    [trim((string) $data['title']), 'event', $relPath, trim((string) $data['title']), (int) $authUser['id'], $now]
                );
                $coverPath = '/media/' . $mediaId;
            } catch (\Throwable $e) {
                $this->flash('error', $e->getMessage());
                return $this->redirect('/admin/events');
            }
        }

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', (string) $data['title']) ?? 'event', '-')) ?: 'event';
        $slug .= '-' . substr(bin2hex(random_bytes(3)), 0, 6);
        $id = Database::insert(
            'INSERT INTO events (title, slug, summary, body, event_date, location, cover_image, is_published, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)',
            [
                trim((string) $data['title']),
                $slug,
                trim((string) ($data['summary'] ?? '')),
                trim((string) ($data['body'] ?? '')),
                $data['event_date'],
                trim((string) ($data['location'] ?? '')),
                $coverPath,
                (int) $authUser['id'],
                $now,
                $now,
            ]
        );

        AuditLogService::log((int) $authUser['id'], 'event.created', 'event', $id);
        $this->flash('success', Translator::get('admin.event_published'));
        return $this->redirect('/admin/events');
    }

    public function deleteEvent(Request $request, array $params): Response
    {
        $id = (int) ($params['id'] ?? 0);
        Database::execute('DELETE FROM events WHERE id = ?', [$id]);
        $this->flash('success', Translator::get('common.delete'));
        return $this->redirect('/admin/events');
    }

    public function settings(Request $request): Response
    {
        return $this->view('panels/super_admin/settings', [
            'title' => Translator::get('menu.settings'),
            'settings' => SettingsService::all(),
            'skyroomDiag' => null,
        ]);
    }

    public function saveSettings(Request $request): Response
    {
        $data = $request->allPost();
        SettingsService::setMany([
            'school_name' => trim((string) ($data['school_name'] ?? '')),
            'school_tagline' => trim((string) ($data['school_tagline'] ?? '')),
            'school_intro' => trim((string) ($data['school_intro'] ?? '')),
            'school_phone' => trim((string) ($data['school_phone'] ?? '')),
            'school_email' => trim((string) ($data['school_email'] ?? '')),
            'school_address' => trim((string) ($data['school_address'] ?? '')),
        ], 'school');

        SettingsService::setMany([
            'hero_title' => trim((string) ($data['hero_title'] ?? '')),
            'hero_subtitle' => trim((string) ($data['hero_subtitle'] ?? '')),
        ], 'cms');

        $defLocale = in_array($data['default_locale'] ?? 'fa', ['fa', 'en'], true) ? $data['default_locale'] : 'fa';
        $defTheme = in_array($data['default_theme'] ?? 'light', ['light', 'dark'], true) ? $data['default_theme'] : 'light';
        SettingsService::setMany([
            'default_locale' => $defLocale,
            'default_theme' => $defTheme,
        ], 'general');

        $skyroomUpdate = [
            'skyroom_enabled' => isset($data['skyroom_enabled']) ? '1' : '0',
            'skyroom_api_url' => trim((string) ($data['skyroom_api_url'] ?? 'https://www.skyroom.online/skyroom/api/')),
        ];
        if (trim((string) ($data['skyroom_api_key'] ?? '')) !== '') {
            $skyroomUpdate['skyroom_api_key'] = trim((string) $data['skyroom_api_key']);
        }
        SettingsService::setMany($skyroomUpdate, 'skyroom');

        $authUser = $this->authUser();
        AuditLogService::log((int) $authUser['id'], 'settings.updated', 'settings', null);
        $this->flash('success', Translator::get('admin.settings_saved'));
        return $this->redirect('/admin/settings');
    }

    public function testSkyroom(Request $request): Response
    {
        $diag = SkyroomService::testConnection();
        $this->flash($diag['ok'] ? 'success' : 'warning', $diag['message']);
        return $this->redirect('/admin/settings');
    }

    public function auditLogs(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $actionFilter = trim((string) $request->query('action', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $where = 'WHERE 1=1';
        $params = [];
        if ($q !== '') {
            $where .= ' AND (u.username LIKE ? OR al.action LIKE ? OR al.entity_type LIKE ? OR al.ip_address LIKE ?)';
            $like = '%' . $q . '%';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }
        if ($actionFilter !== '') {
            $where .= ' AND al.action = ?';
            $params[] = $actionFilter;
        }

        $total = (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM audit_logs al LEFT JOIN users u ON u.id = al.user_id {$where}",
            $params
        );
        $totalPages = max(1, (int) ceil($total / $perPage));
        $logs = Database::fetchAll(
            "SELECT al.*, u.username
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             {$where}
             ORDER BY al.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        $actions = Database::fetchAll('SELECT DISTINCT action FROM audit_logs ORDER BY action ASC');

        return $this->view('panels/super_admin/audit_logs', [
            'title' => Translator::get('menu.audit_logs'),
            'logs' => $logs,
            'actions' => $actions,
            'q' => $q,
            'actionFilter' => $actionFilter,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ]);
    }

    public function health(Request $request): Response
    {
        $t0 = microtime(true);
        $dbOk = false;
        $dbDriver = Database::driver();
        try {
            Database::fetchColumn('SELECT 1');
            $dbOk = true;
        } catch (\Throwable) {
            $dbOk = false;
        }
        $dbLatencyMs = (int) round((microtime(true) - $t0) * 1000);

        $extensions = ['pdo', 'pdo_mysql', 'pdo_sqlite', 'mbstring', 'json', 'fileinfo', 'curl', 'openssl'];
        $extStatus = [];
        foreach ($extensions as $ext) {
            $extStatus[$ext] = extension_loaded($ext);
        }

        $sessionWritable = is_dir(STORAGE_PATH . '/sessions') && is_writable(STORAGE_PATH . '/sessions');
        $uploadsDir = STORAGE_PATH . '/uploads';
        if (!is_dir($uploadsDir)) {
            @mkdir($uploadsDir, 0775, true);
        }
        $uploadsWritable = is_dir($uploadsDir) && is_writable($uploadsDir);

        $parity = Translator::checkConsistency();
        $skyroomDiag = SkyroomService::testConnection();

        return $this->view('panels/super_admin/health', [
            'title' => Translator::get('admin.system_health'),
            'phpVersion' => PHP_VERSION,
            'dbDriver' => $dbDriver,
            'dbOk' => $dbOk,
            'dbLatencyMs' => $dbLatencyMs,
            'extStatus' => $extStatus,
            'sessionWritable' => $sessionWritable,
            'uploadsWritable' => $uploadsWritable,
            'parity' => $parity,
            'skyroomDiag' => $skyroomDiag,
            'supportedLocales' => Config::get('app.supported_locales', ['fa', 'en']),
        ]);
    }
}
