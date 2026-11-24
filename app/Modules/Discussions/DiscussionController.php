<?php

declare(strict_types=1);

namespace App\Modules\Discussions;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Translator;
use App\Core\View;
use App\Modules\Authorization\OwnershipGuard;

final class DiscussionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->authUser();
        $role = $user['role'] ?? '';
        $groups = [];
        $assignments = [];

        if ($role === 'teacher') {
            $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
            $tid = (int) ($teacher['id'] ?? 0);
            $groups = Database::fetchAll(
                'SELECT dg.*, c.name AS class_name, s.name AS subject_name
                 FROM discussion_groups dg
                 JOIN classes c ON c.id = dg.class_id
                 JOIN subjects s ON s.id = dg.subject_id
                 WHERE dg.teacher_id = ?
                 ORDER BY dg.id DESC',
                [$tid]
            );
            $assignments = Database::fetchAll(
                'SELECT ta.class_id, ta.subject_id, c.name AS class_name, s.name AS subject_name
                 FROM teacher_assignments ta
                 JOIN classes c ON c.id = ta.class_id
                 JOIN subjects s ON s.id = ta.subject_id
                 WHERE ta.teacher_id = ?',
                [$tid]
            );
        } elseif ($role === 'student') {
            $student = OwnershipGuard::studentProfile((int) $user['id']);
            $cid = (int) ($student['class_id'] ?? 0);
            $groups = $cid > 0 ? Database::fetchAll(
                'SELECT dg.*, c.name AS class_name, s.name AS subject_name
                 FROM discussion_groups dg
                 JOIN classes c ON c.id = dg.class_id
                 JOIN subjects s ON s.id = dg.subject_id
                 WHERE dg.class_id = ?
                 ORDER BY dg.id DESC',
                [$cid]
            ) : [];
        }

        $activeGroupId = (int) $request->query('group_id', $groups[0]['id'] ?? 0);
        $messages = $activeGroupId > 0 ? Database::fetchAll(
            'SELECT dm.*, u.full_name, u.username
             FROM discussion_messages dm
             JOIN users u ON u.id = dm.user_id
             WHERE dm.group_id = ?
             ORDER BY dm.id ASC',
            [$activeGroupId]
        ) : [];

        return $this->view('shared/discussions', [
            'title' => Translator::get('nav.discussions'),
            'groups' => $groups,
            'assignments' => $assignments,
            'activeGroupId' => $activeGroupId,
            'messages' => $messages,
            'role' => $role,
        ]);
    }

    public function createGroup(Request $request): Response
    {
        $user = $this->authUser();
        $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
        $tid = (int) ($teacher['id'] ?? 0);

        $pair = (string) $request->post('assignment_pair', '');
        [$classId, $subjectId] = array_map('intval', explode(':', $pair) + [0, 0]);
        if (!OwnershipGuard::teacherAssignedToClass($tid, $classId, $subjectId)) {
            return Response::html(View::render('errors/403', ['title' => '403'], 'public'), 403);
        }

        $title = trim((string) $request->post('title', ''));
        if ($title === '') {
            $this->flash('error', Translator::get('validation.required', ['field' => Translator::get('discussions.group_title')]));
            return $this->redirect('/discussions');
        }

        $gid = Database::insert(
            'INSERT INTO discussion_groups (class_id, subject_id, teacher_id, title, description, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$classId, $subjectId, $tid, $title, trim((string) $request->post('description', '')), date('Y-m-d H:i:s')]
        );

        $this->flash('success', Translator::get('discussions.group_created'));
        return $this->redirect('/discussions?group_id=' . $gid);
    }

    public function postMessage(Request $request, array $params): Response
    {
        $user = $this->authUser();
        $groupId = (int) ($params['id'] ?? 0);
        $group = Database::fetch('SELECT * FROM discussion_groups WHERE id = ?', [$groupId]);
        if (!$group) {
            return Response::html(View::render('errors/404', ['title' => '404'], 'public'), 404);
        }

        // Verify user belongs to this group's class/teacher
        $allowed = false;
        if (($user['role'] ?? '') === 'teacher') {
            $teacher = OwnershipGuard::teacherProfile((int) $user['id']);
            $allowed = $teacher && (int) $group['teacher_id'] === (int) $teacher['id'];
        } elseif (($user['role'] ?? '') === 'student') {
            $student = OwnershipGuard::studentProfile((int) $user['id']);
            $allowed = $student && (int) $group['class_id'] === (int) $student['class_id'];
        }
        if (!$allowed) {
            return Response::html(View::render('errors/403', ['title' => '403'], 'public'), 403);
        }

        $msg = trim((string) $request->post('message', ''));
        if ($msg !== '') {
            Database::insert(
                'INSERT INTO discussion_messages (group_id, user_id, message, created_at) VALUES (?, ?, ?, ?)',
                [$groupId, (int) $user['id'], $msg, date('Y-m-d H:i:s')]
            );
        }
        return $this->redirect('/discussions?group_id=' . $groupId);
    }
}
