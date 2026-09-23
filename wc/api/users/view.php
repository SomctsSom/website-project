<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('users.view');

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid user id', 422);
}

$pdo = db();
$stmt = $pdo->prepare(
    'SELECT u.id, u.name, u.email, u.role_id, u.is_active, u.last_login_at, u.created_at, u.updated_at, u.deleted_at,
            r.name AS role_name, r.code AS role_code
     FROM users u
     JOIN roles r ON r.id = u.role_id
     WHERE u.id = :id
     LIMIT 1'
);
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
if (!$row) {
    json_error('User not found', 404);
}
$row['id'] = (int) $row['id'];
$row['role_id'] = (int) $row['role_id'];
$row['is_active'] = (int) $row['is_active'];
$row['created_at_display'] = format_display_time($row['created_at']);
$row['updated_at_display'] = format_display_time($row['updated_at']);
$row['last_login_at_display'] = format_display_time($row['last_login_at']);
json_ok($row);
