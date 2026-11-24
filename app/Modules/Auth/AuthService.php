<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Core\Database;
use App\Core\RateLimiter;
use App\Core\Session;
use App\Core\Translator;
use App\Modules\AuditLogs\AuditLogService;
use App\Modules\Authorization\AuthorizationService;

final class AuthService
{
    private static ?array $cachedUser = null;

    public static function hashPassword(string $plain): string
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        return password_hash($plain, $algo);
    }

    public static function user(): ?array
    {
        $userId = Session::userId();
        if ($userId === null) {
            self::$cachedUser = null;
            return null;
        }
        if (self::$cachedUser !== null && (int) self::$cachedUser['id'] === $userId) {
            return self::$cachedUser;
        }

        $user = Database::fetch(
            'SELECT u.*, r.slug AS role
             FROM users u
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             WHERE u.id = ?
             LIMIT 1',
            [$userId]
        );
        if (!$user || $user['status'] !== 'active') {
            self::$cachedUser = null;
            return null;
        }
        self::$cachedUser = $user;
        return $user;
    }

    public static function clearCache(): void
    {
        self::$cachedUser = null;
    }

    public static function attempt(string $identifier, string $password, string $ip): array
    {
        $cleanId = trim($identifier);
        if (RateLimiter::isLocked($cleanId, $ip)) {
            return ['ok' => false, 'error' => Translator::get('auth.rate_limited')];
        }

        $user = Database::fetch(
            'SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$cleanId, $cleanId]
        );

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            RateLimiter::record($cleanId, $ip, false);
            AuditLogService::log($user ? (int) $user['id'] : null, 'auth.login_failed', 'user', $user ? (int) $user['id'] : null, ['identifier' => $cleanId], $ip);
            return ['ok' => false, 'error' => Translator::get('auth.invalid_credentials')];
        }

        if ($user['status'] !== 'active') {
            RateLimiter::record($cleanId, $ip, false);
            return ['ok' => false, 'error' => Translator::get('auth.account_inactive')];
        }

        RateLimiter::record($cleanId, $ip, true);
        Session::regenerate();
        Session::set('auth_user_id', (int) $user['id']);
        $userLocale = in_array($user['locale'] ?? '', ['fa', 'en'], true) ? $user['locale'] : 'fa';
        Session::set('locale', $userLocale);
        Session::set('theme', in_array($user['theme'] ?? '', ['light', 'dark'], true) ? $user['theme'] : 'light');
        self::$cachedUser = null;

        Database::execute('UPDATE users SET last_login_at = ?, updated_at = ? WHERE id = ?', [
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
            (int) $user['id'],
        ]);

        AuditLogService::log((int) $user['id'], 'auth.login_success', 'user', (int) $user['id'], [], $ip);

        $role = AuthorizationService::userRole((int) $user['id']);
        return [
            'ok' => true,
            'user' => self::user(),
            'role' => $role,
            'must_change_password' => (bool) $user['must_change_password'],
        ];
    }

    public static function logout(): void
    {
        $user = self::user();
        if ($user) {
            AuditLogService::log((int) $user['id'], 'auth.logout', 'user', (int) $user['id']);
        }
        self::$cachedUser = null;
        Session::destroy();
    }

    public static function updatePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $user = Database::fetch('SELECT * FROM users WHERE id = ?', [$userId]);
        if (!$user || !password_verify($currentPassword, (string) $user['password_hash'])) {
            return ['ok' => false, 'error' => Translator::get('auth.current_password_wrong')];
        }

        Database::execute(
            'UPDATE users SET password_hash = ?, must_change_password = 0, updated_at = ? WHERE id = ?',
            [self::hashPassword($newPassword), date('Y-m-d H:i:s'), $userId]
        );
        self::$cachedUser = null;
        AuditLogService::log($userId, 'auth.password_changed', 'user', $userId);
        return ['ok' => true];
    }

    public static function registerPublic(array $input, string $ip): array
    {
        $role = (string) ($input['role'] ?? 'student');
        if (!in_array($role, ['student', 'parent'], true)) {
            return ['ok' => false, 'error' => Translator::get('auth.invalid_register_role')];
        }

        $username = trim((string) ($input['username'] ?? ''));
        if (!preg_match('/^[a-zA-Z0-9_.-]{3,60}$/', $username)) {
            return ['ok' => false, 'error' => Translator::get('auth.invalid_username_format')];
        }

        $fullName = trim((string) ($input['full_name'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));
        $emailRaw = trim((string) ($input['email'] ?? ''));
        $email = $emailRaw !== '' ? $emailRaw : null;
        $password = (string) ($input['password'] ?? '');
        $classId = (int) ($input['class_id'] ?? 0);

        if (RateLimiter::isLocked($username, $ip)) {
            return ['ok' => false, 'error' => Translator::get('auth.rate_limited')];
        }

        $existsUsername = Database::fetch('SELECT id FROM users WHERE username = ? LIMIT 1', [$username]);
        if ($existsUsername) {
            return ['ok' => false, 'error' => Translator::get('auth.username_taken')];
        }

        if ($email !== null) {
            $existsEmail = Database::fetch('SELECT id FROM users WHERE email = ? LIMIT 1', [$email]);
            if ($existsEmail) {
                return ['ok' => false, 'error' => Translator::get('auth.email_taken')];
            }
        }

        $roleRow = Database::fetch('SELECT id FROM roles WHERE slug = ? LIMIT 1', [$role]);
        if (!$roleRow) {
            return ['ok' => false, 'error' => Translator::get('errors.server_error')];
        }

        $targetClassId = null;
        if ($role === 'student') {
            $cls = $classId > 0
                ? Database::fetch('SELECT id FROM classes WHERE id = ? LIMIT 1', [$classId])
                : Database::fetch('SELECT id FROM classes ORDER BY id ASC LIMIT 1');
            if (!$cls) {
                return ['ok' => false, 'error' => Translator::get('auth.class_unavailable')];
            }
            $targetClassId = (int) $cls['id'];
        }

        $now = date('Y-m-d H:i:s');
        $locale = Translator::locale();
        $userId = Database::transaction(static function () use (
            $username,
            $email,
            $phone,
            $password,
            $fullName,
            $locale,
            $roleRow,
            $role,
            $targetClassId,
            $now
        ): int {
            $uid = Database::insert(
                'INSERT INTO users (username, email, phone, password_hash, full_name, locale, theme, status, must_change_password, last_login_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)',
                [
                    $username,
                    $email,
                    $phone !== '' ? $phone : null,
                    self::hashPassword($password),
                    $fullName,
                    $locale,
                    'light',
                    'active',
                    $now,
                    $now,
                    $now,
                ]
            );
            Database::execute('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)', [$uid, (int) $roleRow['id']]);

            if ($role === 'student') {
                $studentCode = 'STU-' . (2000 + $uid);
                Database::insert(
                    'INSERT INTO students (user_id, student_code, class_id, guardian_phone, enrollment_date) VALUES (?, ?, ?, ?, ?)',
                    [$uid, $studentCode, $targetClassId, $phone !== '' ? $phone : null, date('Y-m-d')]
                );
            } else {
                Database::insert(
                    'INSERT INTO parents (user_id, emergency_phone) VALUES (?, ?)',
                    [$uid, $phone !== '' ? $phone : null]
                );
            }

            return $uid;
        });

        Session::regenerate();
        Session::set('auth_user_id', $userId);
        Session::set('locale', $locale);
        Session::set('theme', 'light');
        self::$cachedUser = null;

        AuditLogService::log($userId, 'auth.register', 'user', $userId, ['role' => $role], $ip);

        return [
            'ok' => true,
            'role' => $role,
            'user' => self::user(),
        ];
    }
}
