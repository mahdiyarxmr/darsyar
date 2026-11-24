<?php

declare(strict_types=1);

namespace App\Modules\Notifications;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Translator;

final class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->authUser();
        $notifications = NotificationService::forUser((int) $user['id']);
        return $this->view('shared/notifications', [
            'title' => Translator::get('nav.notifications'),
            'notifications' => $notifications,
        ]);
    }

    public function markRead(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $id = (int) ($params['id'] ?? 0);
        $ok = NotificationService::markRead($id, (int) $user['id']);

        if ($request->isAjax()) {
            return $this->json(['ok' => $ok]);
        }
        return $this->redirect('/notifications');
    }

    public function markAllRead(Request $request): Response
    {
        $user = $this->authUser();
        NotificationService::markAllRead((int) $user['id']);
        return $this->redirect('/notifications');
    }
}
