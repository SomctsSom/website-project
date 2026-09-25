<?php
declare(strict_types=1);

$method = request_method();
$pdo = db();

if ($method === 'GET') {
    auth_require_permission('footer_colors.view');
    json_ok(get_footer_colors($pdo));
}

if ($method === 'POST') {
    $actor = auth_require_permission('footer_colors.edit');
    auth_verify_csrf($actor);
    $input = array_merge(request_input(), $_POST);
    $colors = set_footer_colors($pdo, $input, (int) $actor['id']);
    audit_log((int) $actor['id'], 'update', 'site_settings', null, [
        'setting' => 'footer_colors',
        'colors' => $colors,
    ]);
    json_ok($colors, 'Footer colors updated');
}

json_error('Method not allowed', 405);
