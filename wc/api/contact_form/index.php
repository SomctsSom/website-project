<?php
declare(strict_types=1);

$method = request_method();
$pdo = db();

if ($method === 'GET') {
    auth_require_permission('contact_form.view');
    $settings = get_contact_form_settings($pdo);
    $settings['pages'] = list_contact_form_page_visibility($pdo);
    json_ok($settings);
}

if ($method === 'POST') {
    $actor = auth_require_permission('contact_form.edit');
    auth_verify_csrf($actor);
    $input = array_merge(request_input(), $_POST);
    $settings = set_contact_form_settings($pdo, [
        'show_full_name' => isset($input['show_full_name']) ? $input['show_full_name'] : 0,
        'show_company_name' => isset($input['show_company_name']) ? $input['show_company_name'] : 0,
        'show_phone' => isset($input['show_phone']) ? $input['show_phone'] : 0,
        'show_email' => isset($input['show_email']) ? $input['show_email'] : 0,
        'show_preferred_service' => isset($input['show_preferred_service']) ? $input['show_preferred_service'] : 0,
        'show_message' => isset($input['show_message']) ? $input['show_message'] : 0,
    ], (int) $actor['id']);
    // When using checkboxes from form, missing key means unchecked — set_contact_form_settings
    // already handled via explicit 0/1 above only if we pass them. Fix: always pass all keys.
    audit_log((int) $actor['id'], 'update', 'site_settings', null, [
        'setting' => 'contact_form',
        'settings' => $settings,
    ]);
    json_ok($settings, 'Contact form settings updated');
}

json_error('Method not allowed', 405);
