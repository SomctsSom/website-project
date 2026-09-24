<?php
declare(strict_types=1);

require_once __DIR__ . '/api_client.php';

function wa_user(): ?array
{
    wa_start_session();
    return $_SESSION['user'] ?? null;
}

function wa_require_login(): array
{
    $user = wa_user();
    if ($user === null) {
        wa_redirect('/login');
    }
    return $user;
}

function wa_can(string $permission): bool
{
    $user = wa_user();
    if ($user === null) {
        return false;
    }
    if (($user['role_code'] ?? '') === 'super_admin') {
        return true;
    }
    $perms = $user['permissions'] ?? [];
    return in_array($permission, $perms, true);
}

function wa_require_perm(string $permission): void
{
    wa_require_login();
    if (!wa_can($permission)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function wa_login(string $login, string $password): array
{
    $res = wa_api('POST', '/api/auth/login', ['login' => $login, 'password' => $password]);
    if (!wa_api_ok($res)) {
        return $res;
    }
    wa_start_session();
    session_regenerate_id(true);
    $data = $res['data'];
    $_SESSION['api_token'] = $data['token'];
    $_SESSION['csrf_token'] = $data['csrf_token'];
    $_SESSION['user'] = $data['user'];
    return $res;
}

function wa_logout(): void
{
    wa_start_session();
    if (!empty($_SESSION['api_token'])) {
        wa_api('POST', '/api/auth/logout');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool) $p['secure'], (bool) $p['httponly']);
    }
    session_destroy();
}

function wa_refresh_user(): void
{
    $res = wa_api('GET', '/api/auth/me');
    if (wa_api_ok($res)) {
        $_SESSION['user'] = $res['data']['user'];
        $_SESSION['csrf_token'] = $res['data']['user']['csrf_token'] ?? $_SESSION['csrf_token'];
    } elseif (($res['http_status'] ?? 0) === 401) {
        wa_logout();
        wa_redirect('/login');
    }
}

function wa_csrf_field(): string
{
    wa_start_session();
    $t = wa_e((string) ($_SESSION['csrf_token'] ?? ''));
    return '<input type="hidden" name="_csrf" value="' . $t . '">';
}
