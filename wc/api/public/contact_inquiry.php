<?php
declare(strict_types=1);

require_method('POST');

$pdo = db();
$input = array_merge(request_input(), $_POST);
$settings = get_contact_form_settings($pdo);
$validated = contact_inquiry_validate($input, $settings, $pdo);
if (!$validated['ok']) {
    json_error((string) $validated['error'], 422);
}

$inserted = contact_inquiry_insert($pdo, $validated['data']);
if (!$inserted['ok']) {
    json_error((string) $inserted['error'], 500);
}

json_ok(['id' => (int) $inserted['id']], 'Thank you. Your message has been sent.', 201);
