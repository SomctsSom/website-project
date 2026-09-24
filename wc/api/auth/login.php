<?php
declare(strict_types=1);

require_method('POST');

$input = request_input();
$login = trim((string) ($input['login'] ?? $input['email'] ?? $input['username'] ?? ''));
$password = (string) ($input['password'] ?? '');

if ($login === '' || $password === '') {
    json_error('Name or email and password are required', 422);
}

$loginKey = strtolower($login);
if (auth_login_rate_limited($loginKey)) {
    json_error('Too many login attempts. Try again later.', 429);
}

$pdo = db();
$stmt = $pdo->prepare(
    'SELECT u.*, r.code AS role_code, r.name AS role_name
     FROM users u
     JOIN roles r ON r.id = u.role_id AND r.deleted_at IS NULL
     WHERE u.deleted_at IS NULL
       AND (LOWER(u.email) = :login_a OR LOWER(u.name) = :login_b)
     ORDER BY CASE WHEN LOWER(u.email) = :login_c THEN 0 ELSE 1 END
     LIMIT 1'
);
$stmt->execute([
    ':login_a' => $loginKey,
    ':login_b' => $loginKey,
    ':login_c' => $loginKey,
]);
$user = $stmt->fetch();

if (!$user || (int) $user['is_active'] !== 1 || !password_verify($password, $user['password_hash'])) {
    auth_record_login_attempt($loginKey, false);
    json_error('Invalid credentials', 401);
}

auth_record_login_attempt($loginKey, true);

$pdo->prepare('UPDATE users SET last_login_at = :t WHERE id = :id')
    ->execute([':t' => now_utc(), ':id' => $user['id']]);

$session = auth_create_session((int) $user['id']);
audit_log((int) $user['id'], 'login', 'users', (int) $user['id'], [
    'login' => $login,
    'email' => $user['email'],
]);

json_ok([
    'token' => $session['token'],
    'csrf_token' => $session['csrf_token'],
    'expires_at' => $session['expires_at'],
    'user' => [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role_id' => (int) $user['role_id'],
        'role_code' => $user['role_code'],
        'role_name' => $user['role_name'],
        'permissions' => auth_permissions((int) $user['id']),
    ],
], 'Logged in');
