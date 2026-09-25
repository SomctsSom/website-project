<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('contact_form.edit');
auth_verify_csrf($actor);

$pdo = db();
$input = array_merge(request_input(), $_POST);
$id = (int) ($input['id'] ?? 0);
$result = delete_contact_form_field($pdo, $id, (int) $actor['id']);
if (!$result['ok']) {
    json_error((string) $result['error'], 422);
}

audit_log((int) $actor['id'], 'delete', 'contact_form_fields', $id, [
    'soft' => true,
]);

json_ok(['id' => $id], 'Custom field removed');
