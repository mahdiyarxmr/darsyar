<?php

declare(strict_types=1);

namespace App\Modules\CMS;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Translator;
use App\Core\Uploader;
use App\Modules\Settings\SettingsService;

final class PublicController extends Controller
{
    public function home(Request $request): Response
    {
        $settings = SettingsService::all();
        $announcements = Database::fetchAll(
            'SELECT * FROM announcements WHERE is_public = 1 ORDER BY published_at DESC LIMIT 4'
        );
        $news = Database::fetchAll(
            "SELECT * FROM cms_posts WHERE type = 'news' AND is_published = 1 ORDER BY sort_order ASC, id DESC LIMIT 4"
        );
        $banners = Database::fetchAll(
            "SELECT * FROM cms_posts WHERE type = 'banner' AND is_published = 1 ORDER BY sort_order ASC, id DESC LIMIT 3"
        );
        $activities = Database::fetchAll(
            "SELECT * FROM cms_posts WHERE type = 'activity' AND is_published = 1 ORDER BY sort_order ASC, id DESC LIMIT 4"
        );
        $events = Database::fetchAll(
            'SELECT * FROM events WHERE is_published = 1 ORDER BY event_date DESC LIMIT 4'
        );
        $gallery = Database::fetchAll(
            "SELECT * FROM media WHERE category IN ('gallery','event','banner') ORDER BY id DESC LIMIT 8"
        );

        return $this->view('public/home', [
            'title' => $settings['school_name'] ?? Translator::get('app.name'),
            'schoolName' => $settings['school_name'] ?? Translator::get('app.name'),
            'settings' => $settings,
            'announcements' => $announcements,
            'news' => $news,
            'banners' => $banners,
            'activities' => $activities,
            'events' => $events,
            'gallery' => $gallery,
        ], 'public');
    }

    public function news(Request $request): Response
    {
        $settings = SettingsService::all();
        $posts = Database::fetchAll(
            "SELECT * FROM cms_posts WHERE type IN ('news','activity') AND is_published = 1 ORDER BY id DESC"
        );
        return $this->view('public/news', [
            'title' => Translator::get('public.school_news'),
            'schoolName' => $settings['school_name'] ?? Translator::get('app.name'),
            'posts' => $posts,
        ], 'public');
    }

    public function events(Request $request): Response
    {
        $settings = SettingsService::all();
        $events = Database::fetchAll(
            'SELECT * FROM events WHERE is_published = 1 ORDER BY event_date DESC'
        );
        $gallery = Database::fetchAll(
            "SELECT * FROM media WHERE category IN ('gallery','event') ORDER BY id DESC LIMIT 12"
        );
        return $this->view('public/events', [
            'title' => Translator::get('public.upcoming_events'),
            'schoolName' => $settings['school_name'] ?? Translator::get('app.name'),
            'events' => $events,
            'gallery' => $gallery,
        ], 'public');
    }

    public function servePublicMedia(Request $request, array $params): Response
    {
        $id = (int) ($params['id'] ?? 0);
        $media = Database::fetch('SELECT * FROM media WHERE id = ?', [$id]);
        if (!$media) {
            return Response::json(['error' => 'Not found'], 404);
        }
        $path = Uploader::resolveStoragePath((string) $media['file_path']);
        if (!$path || !is_file($path)) {
            return Response::json(['error' => 'File not found'], 404);
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
        return new Response(file_get_contents($path) ?: '', 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
