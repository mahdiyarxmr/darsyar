<?php

declare(strict_types=1);

namespace App\Modules\Parents;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Translator;
use App\Core\View;
use App\Modules\Attendance\AttendanceService;
use App\Modules\Authorization\OwnershipGuard;
use App\Modules\Counseling\CounselingService;
use App\Modules\Exams\DailyTestService;
use App\Modules\Reports\PerformanceRatingService;

final class ParentController extends Controller
{
    public function dashboard(Request $request): Response
    {
        [$children, $activeChild, $forbidden] = $this->resolveChild($request);
        if ($forbidden) {
            return Response::html(View::render('errors/403', ['title' => '403'], 'public'), 403);
        }

        $sid = $activeChild ? (int) $activeChild['id'] : 0;
        $rating = $sid > 0 ? PerformanceRatingService::evaluateStudent($sid) : [];
        $announcements = Database::fetchAll(
            "SELECT * FROM announcements WHERE target_role IN ('all','parents') ORDER BY published_at DESC LIMIT 5"
        );
        $onlineParticipation = $sid > 0 ? Database::fetchAll(
            'SELECT ocs.title, ocs.scheduled_start, s.name AS subject_name, oca.status, oca.joined_at
             FROM online_class_attendance oca
             JOIN online_class_sessions ocs ON ocs.id = oca.session_id
             JOIN subjects s ON s.id = ocs.subject_id
             WHERE oca.student_id = ?
             ORDER BY ocs.scheduled_start DESC LIMIT 8',
            [$sid]
        ) : [];
        $counselorNotes = $sid > 0 ? Database::fetchAll(
            'SELECT cn.*, cu.full_name AS counselor_name
             FROM counseling_notes cn
             JOIN counselors c ON c.id = cn.counselor_id
             JOIN users cu ON cu.id = c.user_id
             WHERE cn.student_id = ? AND cn.visible_to_parent = 1
             ORDER BY cn.id DESC LIMIT 5',
            [$sid]
        ) : [];

        return $this->view('panels/parent/dashboard', [
            'title' => Translator::get('panel.parent'),
            'children' => $children,
            'activeChild' => $activeChild,
            'rating' => $rating,
            'announcements' => $announcements,
            'onlineParticipation' => $onlineParticipation,
            'counselorNotes' => $counselorNotes,
        ]);
    }

    public function exams(Request $request): Response
    {
        [$children, $activeChild, $forbidden] = $this->resolveChild($request);
        if ($forbidden) {
            return Response::html(View::render('errors/403', ['title' => '403'], 'public'), 403);
        }
        $sid = $activeChild ? (int) $activeChild['id'] : 0;

        $examResults = $sid > 0 ? Database::fetchAll(
            'SELECT er.*, e.title AS exam_title, e.exam_mode, s.name AS subject_name
             FROM exam_results er
             JOIN exams e ON e.id = er.exam_id
             JOIN subjects s ON s.id = e.subject_id
             WHERE er.student_id = ?
             ORDER BY er.published_at DESC',
            [$sid]
        ) : [];
        $dailyStats = $sid > 0 ? DailyTestService::studentSubjectStats($sid) : [];

        return $this->view('panels/parent/exams', [
            'title' => Translator::get('menu.exams'),
            'children' => $children,
            'activeChild' => $activeChild,
            'examResults' => $examResults,
            'dailyStats' => $dailyStats,
        ]);
    }

    public function homework(Request $request): Response
    {
        [$children, $activeChild, $forbidden] = $this->resolveChild($request);
        if ($forbidden) {
            return Response::html(View::render('errors/403', ['title' => '403'], 'public'), 403);
        }
        $sid = $activeChild ? (int) $activeChild['id'] : 0;
        $cid = $activeChild ? (int) ($activeChild['class_id'] ?? 0) : 0;

        $items = ($sid > 0 && $cid > 0) ? Database::fetchAll(
            'SELECT h.title, h.due_at, h.max_score, s.name AS subject_name,
                    hs.status AS sub_status, hs.submitted_at,
                    hf.score, hf.feedback_text, hf.decision
             FROM homework h
             JOIN subjects s ON s.id = h.subject_id
             LEFT JOIN homework_submissions hs ON hs.homework_id = h.id AND hs.student_id = ?
             LEFT JOIN homework_feedback hf ON hf.submission_id = hs.id
             WHERE h.class_id = ?
             ORDER BY h.due_at DESC',
            [$sid, $cid]
        ) : [];

        return $this->view('panels/parent/homework', [
            'title' => Translator::get('menu.homework'),
            'children' => $children,
            'activeChild' => $activeChild,
            'items' => $items,
        ]);
    }

    public function studyPlans(Request $request): Response
    {
        [$children, $activeChild, $forbidden] = $this->resolveChild($request);
        if ($forbidden) {
            return Response::html(View::render('errors/403', ['title' => '403'], 'public'), 403);
        }
        $sid = $activeChild ? (int) $activeChild['id'] : 0;

        $plans = $sid > 0 ? Database::fetchAll(
            'SELECT sp.*, u.full_name AS counselor_name FROM study_plans sp JOIN counselors c ON c.id = sp.counselor_id JOIN users u ON u.id = c.user_id WHERE sp.student_id = ? ORDER BY sp.id DESC',
            [$sid]
        ) : [];
        foreach ($plans as &$p) {
            $p['tasks'] = Database::fetchAll(
                'SELECT t.*, sub.name AS subject_name FROM study_plan_tasks t LEFT JOIN subjects sub ON sub.id = t.subject_id WHERE t.study_plan_id = ? ORDER BY t.id ASC',
                [(int) $p['id']]
            );
            $p['progress'] = CounselingService::planProgress((int) $p['id']);
        }
        unset($p);

        return $this->view('panels/parent/study_plans', [
            'title' => Translator::get('menu.study_plans'),
            'children' => $children,
            'activeChild' => $activeChild,
            'plans' => $plans,
        ]);
    }

    public function attendance(Request $request): Response
    {
        [$children, $activeChild, $forbidden] = $this->resolveChild($request);
        if ($forbidden) {
            return Response::html(View::render('errors/403', ['title' => '403'], 'public'), 403);
        }
        $sid = $activeChild ? (int) $activeChild['id'] : 0;
        $summary = $sid > 0 ? AttendanceService::studentAttendanceSummary($sid) : ['records' => [], 'total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'rate' => 100];

        return $this->view('panels/parent/attendance', [
            'title' => Translator::get('menu.attendance'),
            'children' => $children,
            'activeChild' => $activeChild,
            'summary' => $summary,
        ]);
    }

    public function reportCards(Request $request): Response
    {
        [$children, $activeChild, $forbidden] = $this->resolveChild($request);
        if ($forbidden) {
            return Response::html(View::render('errors/403', ['title' => '403'], 'public'), 403);
        }
        $sid = $activeChild ? (int) $activeChild['id'] : 0;

        $cards = $sid > 0 ? Database::fetchAll(
            'SELECT rc.*, ay.name AS year_name FROM report_cards rc JOIN academic_years ay ON ay.id = rc.academic_year_id WHERE rc.student_id = ? AND rc.is_published = 1 ORDER BY rc.id DESC',
            [$sid]
        ) : [];

        return $this->view('panels/parent/report_cards', [
            'title' => Translator::get('menu.report_cards'),
            'children' => $children,
            'activeChild' => $activeChild,
            'cards' => $cards,
        ]);
    }

    public function timetables(Request $request): Response
    {
        [$children, $activeChild, $forbidden] = $this->resolveChild($request);
        if ($forbidden) {
            return Response::html(View::render('errors/403', ['title' => '403'], 'public'), 403);
        }
        $cid = $activeChild ? (int) ($activeChild['class_id'] ?? 0) : 0;
        $timetables = $cid > 0 ? Database::fetchAll(
            'SELECT t.*, ay.name AS year_name FROM timetables t JOIN academic_years ay ON ay.id = t.academic_year_id WHERE t.class_id = ? ORDER BY t.id DESC',
            [$cid]
        ) : [];

        return $this->view('panels/parent/timetables', [
            'title' => Translator::get('menu.timetables'),
            'children' => $children,
            'activeChild' => $activeChild,
            'timetables' => $timetables,
        ]);
    }

    /**
     * Strict Anti-IDOR resolver: if ?student_id= is passed, verifies parent_student ownership.
     * @return array{0: array, 1: ?array, 2: bool}
     */
    private function resolveChild(Request $request): array
    {
        $user = $this->authUser();
        $parentUserId = (int) $user['id'];
        $children = OwnershipGuard::parentChildren($parentUserId);

        $requestedId = (int) $request->query('student_id', 0);
        if ($requestedId > 0) {
            if (!OwnershipGuard::parentOwnsStudent($parentUserId, $requestedId)) {
                return [$children, null, true];
            }
            foreach ($children as $c) {
                if ((int) $c['id'] === $requestedId) {
                    return [$children, $c, false];
                }
            }
            return [$children, null, true];
        }

        return [$children, $children[0] ?? null, false];
    }
}
