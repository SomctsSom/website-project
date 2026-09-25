<?php
declare(strict_types=1);

$method = request_method();
$pdo = db();

if ($method === 'GET') {
    auth_require_permission('website_header.view');
    json_ok(get_header_bar_settings($pdo));
}

if ($method === 'POST') {
    $actor = auth_require_permission('website_header.edit');
    auth_verify_csrf($actor);
    $input = array_merge(request_input(), $_POST);
    $settings = set_header_bar_settings($pdo, $input, (int) $actor['id']);
    audit_log((int) $actor['id'], 'update', 'site_settings', null, [
        'setting' => 'website_header',
        'settings' => $settings,
    ]);
    json_ok($settings, 'Website header settings updated');
}

json_error('Method not allowed', 405);
