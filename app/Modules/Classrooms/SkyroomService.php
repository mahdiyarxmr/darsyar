<?php

declare(strict_types=1);

namespace App\Modules\Classrooms;

use App\Core\Config;
use App\Core\Database;
use App\Core\Translator;
use App\Modules\Notifications\NotificationService;
use App\Modules\Settings\SettingsService;

final class SkyroomService
{
    public static function isConfigured(): bool
    {
        $enabled = SettingsService::get('skyroom_enabled', Config::get('skyroom.enabled') ? '1' : '0');
        $apiKey = SettingsService::get('skyroom_api_key', (string) Config::get('skyroom.api_key', ''));
        return $enabled === '1' && trim((string) $apiKey) !== '';
    }

    /**
     * Live API diagnostic probe. Never fakes success when credentials are absent.
     *
     * @return array{ok: bool, configured: bool, latency_ms: int, message: string}
     */
    public static function testConnection(): array
    {
        if (!self::isConfigured()) {
            return [
                'ok' => false,
                'configured' => false,
                'latency_ms' => 0,
                'message' => Translator::get('skyroom.not_configured'),
            ];
        }

        $t0 = microtime(true);
        $res = self::callApi('getServices', []);
        $ms = (int) round((microtime(true) - $t0) * 1000);

        if ($res['ok']) {
            return [
                'ok' => true,
                'configured' => true,
                'latency_ms' => $ms,
                'message' => Translator::get('skyroom.test_success'),
            ];
        }

        return [
            'ok' => false,
            'configured' => true,
            'latency_ms' => $ms,
            'message' => Translator::get('skyroom.test_failed', ['error' => $res['error'] ?? 'Unknown error']),
        ];
    }

    /**
     * Calls the official Skyroom JSON-RPC HTTP API when live credentials are configured.
     * Never fakes a successful remote room creation when credentials are missing.
     */
    public static function callApi(string $action, array $params = []): array
    {
        if (!self::isConfigured()) {
            return ['ok' => false, 'error' => 'Skyroom API credentials are not configured.'];
        }

        $baseUrl = rtrim((string) SettingsService::get('skyroom_api_url', (string) Config::get('skyroom.api_url')), '/');
        $apiKey = trim((string) SettingsService::get('skyroom_api_key', (string) Config::get('skyroom.api_key')));
        $endpoint = $baseUrl . '/' . $apiKey;

        $payload = json_encode(['action' => $action, 'params' => $params], JSON_UNESCAPED_UNICODE);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => (int) Config::get('skyroom.timeout', 15),
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $httpCode >= 400) {
            return ['ok' => false, 'error' => $err ?: "HTTP {$httpCode}"];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !($decoded['ok'] ?? false)) {
            return ['ok' => false, 'error' => $decoded['error_message'] ?? 'Skyroom API error'];
        }

        return ['ok' => true, 'result' => $decoded['result'] ?? null];
    }

    public static function createSession(
        int $teacherId,
        int $classId,
        int $subjectId,
        string $title,
        string $description,
        string $scheduledStart,
        string $scheduledEnd,
        ?string $fallbackUrl
    ): array {
        $now = date('Y-m-d H:i:s');
        $provider = 'external';
        $externalRoomId = null;
        $joinUrl = null;
        $warning = null;

        if (self::isConfigured()) {
            $slug = 'class-' . $classId . '-' . $subjectId . '-' . time();
            $apiRes = self::callApi('createRoom', [
                'name' => $slug,
                'title' => $title,
                'max_users' => 60,
                'guest_login' => false,
            ]);
            if ($apiRes['ok']) {
                $provider = 'skyroom';
                $externalRoomId = (string) ($apiRes['result'] ?? $slug);
            } else {
                $warning = Translator::get('skyroom.test_failed', ['error' => $apiRes['error']]);
            }
        } else {
            $warning = Translator::get('skyroom.not_configured');
        }

        $sessionId = Database::insert(
            'INSERT INTO online_class_sessions (teacher_id, class_id, subject_id, title, description, scheduled_start, scheduled_end, provider, external_room_id, join_url, fallback_url, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$teacherId, $classId, $subjectId, $title, $description, $scheduledStart, $scheduledEnd, $provider, $externalRoomId, $joinUrl, $fallbackUrl ?: null, 'scheduled', $now, $now]
        );

        NotificationService::notifyClassStudents(
            $classId,
            'online_class',
            Translator::get('notif.online_class_title', ['title' => $title]),
            $scheduledStart,
            '/student/classrooms'
        );

        return ['id' => $sessionId, 'provider' => $provider, 'warning' => $warning];
    }

    public static function resolveJoinUrl(array $session, array $user, bool $isTeacher): ?string
    {
        if ($session['provider'] === 'skyroom' && !empty($session['external_room_id']) && self::isConfigured()) {
            $res = self::callApi('createLoginUrl', [
                'room_id' => (int) $session['external_room_id'],
                'user_id' => 'user_' . $user['id'],
                'nickname' => $user['full_name'],
                'access' => $isTeacher ? 3 : 1,
                'concurrent' => 1,
                'ttl' => 3600,
            ]);
            if ($res['ok'] && is_string($res['result'])) {
                return $res['result'];
            }
        }
        return $session['join_url'] ?: $session['fallback_url'] ?: null;
    }
}
