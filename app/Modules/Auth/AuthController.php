<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Translator;
use App\Core\Validator;
use App\Modules\Authorization\AuthorizationService;

final class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        $user = $this->authUser();
        if ($user) {
            return $this->redirect(AuthorizationService::dashboardRouteForRole($user['role'] ?? null));
        }

        return $this->view('auth/login', [
            'title' => Translator::get('auth.login_title'),
            'mode' => 'login',
            'classes' => Database::fetchAll('SELECT c.id, c.name, g.name AS grade_name FROM classes c LEFT JOIN grades g ON g.id = c.grade_id ORDER BY c.name ASC'),
        ], 'public');
    }

    public function showRegister(Request $request): Response
    {
        $user = $this->authUser();
        if ($user) {
            return $this->redirect(AuthorizationService::dashboardRouteForRole($user['role'] ?? null));
        }

        return $this->view('auth/login', [
            'title' => Translator::get('auth.register_title'),
            'mode' => 'register',
            'classes' => Database::fetchAll('SELECT c.id, c.name, g.name AS grade_name FROM classes c LEFT JOIN grades g ON g.id = c.grade_id ORDER BY c.name ASC'),
        ], 'public');
    }

    public function register(Request $request): Response
    {
        $validator = new Validator();
        $data = $request->allPost();
        if (!$validator->validate($data, [
            'full_name' => 'required|min:2|max:150',
            'username' => 'required|min:3|max:60',
            'email' => 'email|max:191',
            'password' => 'required|min:8|max:128',
            'confirm_password' => 'required',
            'role' => 'required|in:student,parent',
        ])) {
            $this->flash('error', (string) $validator->firstError());
            return $this->redirect('/register');
        }

        if ((string) $data['password'] !== (string) $data['confirm_password']) {
            $this->flash('error', Translator::get('auth.password_mismatch'));
            return $this->redirect('/register');
        }

        $res = AuthService::registerPublic($data, $request->ip());
        if (!$res['ok']) {
            $this->flash('error', (string) $res['error']);
            return $this->redirect('/register');
        }

        $this->flash('success', Translator::get('auth.registered_welcome'));
        return $this->redirect(AuthorizationService::dashboardRouteForRole($res['role']));
    }

    public function login(Request $request): Response
    {
        $validator = new Validator();
        $data = $request->allPost();
        if (!$validator->validate($data, [
            'identifier' => 'required|min:2|max:191',
            'password' => 'required|min:4|max:200',
        ])) {
            $this->flash('error', (string) $validator->firstError());
            return $this->redirect('/login');
        }

        $result = AuthService::attempt(
            (string) $data['identifier'],
            (string) $data['password'],
            $request->ip()
        );

        if (!$result['ok']) {
            $this->flash('error', $result['error']);
            return $this->redirect('/login');
        }

        if ($result['must_change_password']) {
            $this->flash('warning', Translator::get('auth.must_change_password'));
            return $this->redirect('/account/password');
        }

        return $this->redirect(AuthorizationService::dashboardRouteForRole($result['role']));
    }

    public function logout(Request $request): Response
    {
        AuthService::logout();
        Session::start();
        Session::flash('success', Translator::get('auth.logged_out'));
        return $this->redirect('/login');
    }

    public function dashboardRedirect(Request $request): Response
    {
        $user = $this->authUser();
        if (!$user) {
            return $this->redirect('/login');
        }
        return $this->redirect(AuthorizationService::dashboardRouteForRole($user['role'] ?? null));
    }

    public function showChangePassword(Request $request): Response
    {
        return $this->view('shared/change_password', [
            'title' => Translator::get('nav.password'),
        ]);
    }

    public function updatePassword(Request $request): Response
    {
        $user = $this->authUser();
        $data = $request->allPost();
        $validator = new Validator();
        if (!$validator->validate($data, [
            'current_password' => 'required',
            'new_password' => 'required|min:8|max:128',
            'confirm_password' => 'required',
        ])) {
            $this->flash('error', (string) $validator->firstError());
            return $this->redirect('/account/password');
        }

        if ($data['new_password'] !== $data['confirm_password']) {
            $this->flash('error', Translator::get('auth.password_mismatch'));
            return $this->redirect('/account/password');
        }

        $res = AuthService::updatePassword(
            (int) $user['id'],
            (string) $data['current_password'],
            (string) $data['new_password']
        );

        if (!$res['ok']) {
            $this->flash('error', $res['error']);
            return $this->redirect('/account/password');
        }

        $this->flash('success', Translator::get('auth.password_updated'));
        return $this->redirect(AuthorizationService::dashboardRouteForRole($user['role'] ?? null));
    }

    public function switchLocale(Request $request, array $params): Response
    {
        $lang = (string) ($params['lang'] ?? 'fa');
        $supported = Config::get('app.supported_locales', ['fa', 'en']);
        if (in_array($lang, is_array($supported) ? $supported : ['fa', 'en'], true)) {
            Session::set('locale', $lang);
            Translator::setLocale($lang);
            $user = $this->authUser();
            if ($user) {
                Database::execute('UPDATE users SET locale = ? WHERE id = ?', [$lang, (int) $user['id']]);
                AuthService::clearCache();
            }
        }

        $target = '/';
        $redirectParam = (string) $request->query('redirect', '');
        if ($redirectParam !== '' && str_starts_with($redirectParam, '/') && !str_starts_with($redirectParam, '//')) {
            $target = $redirectParam;
        } else {
            $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
            if ($referer !== '') {
                $parsed = parse_url($referer);
                $host = $_SERVER['HTTP_HOST'] ?? '';
                $refHost = ($parsed['host'] ?? '') . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
                if (($parsed['host'] ?? '') === '' || $refHost === $host || ($parsed['host'] ?? '') === explode(':', $host)[0]) {
                    $path = $parsed['path'] ?? '/';
                    $query = isset($parsed['query']) && $parsed['query'] !== '' ? '?' . $parsed['query'] : '';
                    $target = (str_starts_with($path, '/') ? $path : '/' . $path) . $query;
                }
            }
        }
        return Response::redirect($target);
    }

    public function switchTheme(Request $request): Response
    {
        $theme = (string) $request->input('theme', 'light');
        if (!in_array($theme, ['light', 'dark'], true)) {
            $theme = 'light';
        }
        Session::set('theme', $theme);
        $user = $this->authUser();
        if ($user) {
            Database::execute('UPDATE users SET theme = ? WHERE id = ?', [$theme, (int) $user['id']]);
            AuthService::clearCache();
        }
        return $this->json(['ok' => true, 'theme' => $theme]);
    }
}
