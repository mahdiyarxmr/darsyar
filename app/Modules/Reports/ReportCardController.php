<?php

declare(strict_types=1);

namespace App\Modules\Reports;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Uploader;
use App\Core\View;
use App\Modules\Authorization\OwnershipGuard;

final class ReportCardController extends Controller
{
    public function printCard(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $cardId = (int) ($params['id'] ?? 0);
        $card = ReportCardService::findWithItems($cardId);
        if (!$card) {
            return Response::html(View::render('errors/404', ['title' => '404'], 'public'), 404);
        }

        $role = $user['role'] ?? '';
        $authorized = in_array($role, ['super_admin', 'director', 'vice_principal'], true)
            || ($role === 'student' && (int) $card['student_user_id'] === (int) $user['id'])
            || ($role === 'parent' && OwnershipGuard::parentOwnsStudent((int) $user['id'], (int) $card['student_id']));

        if (!$authorized) {
            return Response::html(View::render('errors/403', ['title' => '403'], 'public'), 403);
        }

        return $this->view('shared/report_card_print', [
            'title' => \App\Core\Translator::get('report_card.official_title') . ' - ' . $card['student_name'],
            'card' => $card,
        ], 'print');
    }

    public function downloadFile(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $category = (string) ($params['category'] ?? '');
        $id = (int) ($params['id'] ?? 0);
        $role = $user['role'] ?? '';

        $relPath = null;
        if ($category === 'homework') {
            $hw = Database::fetch('SELECT * FROM homework WHERE id = ?', [$id]);
            if ($hw) {
                if ($role === 'teacher') {
                    $t = OwnershipGuard::teacherProfile((int) $user['id']);
                    if ($t && (int) $hw['teacher_id'] === (int) $t['id']) {
                        $relPath = $hw['attachment_path'];
                    }
                } elseif ($role === 'student') {
                    $s = OwnershipGuard::studentProfile((int) $user['id']);
                    if ($s && (int) $hw['class_id'] === (int) $s['class_id']) {
                        $relPath = $hw['attachment_path'];
                    }
                } elseif (in_array($role, ['super_admin', 'director', 'vice_principal'], true)) {
                    $relPath = $hw['attachment_path'];
                }
            }
        } elseif ($category === 'submission') {
            $sub = Database::fetch(
                'SELECT hs.*, h.teacher_id, s.user_id AS student_user_id
                 FROM homework_submissions hs
                 JOIN homework h ON h.id = hs.homework_id
                 JOIN students s ON s.id = hs.student_id
                 WHERE hs.id = ?',
                [$id]
            );
            if ($sub) {
                if ($role === 'teacher') {
                    $t = OwnershipGuard::teacherProfile((int) $user['id']);
                    if ($t && (int) $sub['teacher_id'] === (int) $t['id']) {
                        $relPath = $sub['attachment_path'];
                    }
                } elseif ($role === 'student' && (int) $sub['student_user_id'] === (int) $user['id']) {
                    $relPath = $sub['attachment_path'];
                } elseif ($role === 'parent' && OwnershipGuard::parentOwnsStudent((int) $user['id'], (int) $sub['student_id'])) {
                    $relPath = $sub['attachment_path'];
                }
            }
        } elseif ($category === 'timetable') {
            $tt = Database::fetch('SELECT * FROM timetables WHERE id = ?', [$id]);
            if ($tt && $this->canAccessClassResource($user, (int) $tt['class_id'])) {
                $relPath = $tt['file_path'];
            }
        } elseif ($category === 'exam') {
            $ex = Database::fetch('SELECT * FROM exams WHERE id = ?', [$id]);
            if ($ex && $this->canAccessClassResource($user, (int) $ex['class_id'], (int) $ex['teacher_id'])) {
                $relPath = $ex['practice_sheet_path'];
            }
        }

        if (!$relPath) {
            return Response::html(View::render('errors/403', ['title' => '403'], 'public'), 403);
        }

        $full = Uploader::resolveStoragePath($relPath);
        if (!$full || !is_file($full)) {
            return Response::html(View::render('errors/404', ['title' => '404'], 'public'), 404);
        }

        return Response::fileDownload($full, basename($full));
    }

    private function canAccessClassResource(array $user, int $classId, ?int $teacherOwnerId = null): bool
    {
        $role = $user['role'] ?? '';
        if (in_array($role, ['super_admin', 'director', 'vice_principal', 'counselor'], true)) {
            return true;
        }
        if ($role === 'teacher') {
            $t = OwnershipGuard::teacherProfile((int) $user['id']);
            if (!$t) {
                return false;
            }
            if ($teacherOwnerId !== null && (int) $t['id'] === $teacherOwnerId) {
                return true;
            }
            return OwnershipGuard::teacherAssignedToClass((int) $t['id'], $classId);
        }
        if ($role === 'student') {
            $s = OwnershipGuard::studentProfile((int) $user['id']);
            return $s && (int) $s['class_id'] === $classId;
        }
        if ($role === 'parent') {
            foreach (OwnershipGuard::parentChildren((int) $user['id']) as $child) {
                if ((int) $child['class_id'] === $classId) {
                    return true;
                }
            }
        }
        return false;
    }
}
