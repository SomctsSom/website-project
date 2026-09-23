<?php
declare(strict_types=1);

require_method('POST');

$input = request_input();
$email = strtolower(trim((string) ($input['email'] ?? '')));
$password = (string) ($input['password'] ?? '');

if ($email === '' || $password === '') {
    json_error('Email and password are required', 422);
}

if (auth_login_rate_limited($email)) {
    json_error('Too many login attempts. Try again later.', 429);
}

$pdo = db();
$stmt = $pdo->prepare(
    'SELECT u.*, r.code AS role_code, r.name AS role_name
     FROM users u
     JOIN roles r ON r.id = u.role_id AND r.deleted_at IS NULL
     WHERE u.email_active = :email
     LIMIT 1'
);
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user || (int) $user['is_active'] !== 1 || !password_verify($password, $user['password_hash'])) {
    auth_record_login_attempt($email, false);
    json_error('Invalid credentials', 401);
}

auth_record_login_attempt($email, true);

$pdo->prepare('UPDATE users SET last_login_at = :t WHERE id = :id')
    ->execute([':t' => now_utc(), ':id' => $user['id']]);

$session = auth_create_session((int) $user['id']);
audit_log((int) $user['id'], 'login', 'users', (int) $user['id'], ['email' => $email]);

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
