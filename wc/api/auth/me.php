<?php
declare(strict_types=1);

require_method('GET');
$user = auth_require_user();
json_ok([
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role_id' => $user['role_id'],
        'role_code' => $user['role_code'],
        'role_name' => $user['role_name'],
        'permissions' => auth_permissions((int) $user['id']),
        'csrf_token' => $user['csrf_token'],
    ],
]);
