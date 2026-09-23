<?php
declare(strict_types=1);

require_method('POST');
$user = auth_require_user();
auth_verify_csrf($user);
auth_revoke_session();
audit_log((int) $user['id'], 'logout', 'users', (int) $user['id']);
json_ok(null, 'Logged out');
