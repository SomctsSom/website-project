<?php
declare(strict_types=1);

$method = request_method();
$pdo = db();

if ($method === 'GET') {
    auth_require_permission('hero.view');
    $preset = get_hero_size_preset($pdo);
    json_ok([
        'size_preset' => $preset,
        'size_label' => hero_size_presets()[$preset],
        'options' => hero_size_presets(),
    ]);
}

if ($method === 'POST') {
    $actor = auth_require_permission('hero.edit');
    auth_verify_csrf($actor);
    $input = array_merge(request_input(), $_POST);
    $preset = set_hero_size_preset(
        $pdo,
        (string) ($input['size_preset'] ?? 'md'),
        (int) $actor['id']
    );
    audit_log((int) $actor['id'], 'update', 'site_settings', null, [
        'setting_key' => 'hero_size_preset',
        'size_preset' => $preset,
    ]);
    json_ok([
        'size_preset' => $preset,
        'size_label' => hero_size_presets()[$preset],
    ], 'Hero section size updated');
}

json_error('Method not allowed', 405);
