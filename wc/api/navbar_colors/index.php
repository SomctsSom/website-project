<?php
declare(strict_types=1);

$method = request_method();
$pdo = db();

if ($method === 'GET') {
    auth_require_permission('navbar_colors.view');
    json_ok(get_navbar_colors($pdo));
}

if ($method === 'POST') {
    $actor = auth_require_permission('navbar_colors.edit');
    auth_verify_csrf($actor);
    $input = array_merge(request_input(), $_POST);
    $colors = set_navbar_colors($pdo, $input, (int) $actor['id']);
    audit_log((int) $actor['id'], 'update', 'site_settings', null, [
        'setting' => 'navbar_colors',
        'colors' => $colors,
    ]);
    json_ok($colors, 'Navbar colors updated');
}

json_error('Method not allowed', 405);
