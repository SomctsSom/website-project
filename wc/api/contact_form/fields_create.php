<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('contact_form.edit');
auth_verify_csrf($actor);

$pdo = db();
$input = array_merge(request_input(), $_POST);
$result = create_contact_form_field($pdo, $input, (int) $actor['id']);
if (!$result['ok']) {
    json_error((string) $result['error'], 422);
}

audit_log((int) $actor['id'], 'create', 'contact_form_fields', (int) ($result['field']['id'] ?? 0), [
    'field' => $result['field'],
]);

json_ok($result['field'], 'Custom field added', 201);
