<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('contact_form.edit');
auth_verify_csrf($actor);

$pdo = db();
$input = array_merge(request_input(), $_POST);
$pageIds = $input['page_ids'] ?? [];
if (!is_array($pageIds)) {
    if (is_string($pageIds) && $pageIds !== '') {
        $decoded = json_decode($pageIds, true);
        if (is_array($decoded)) {
            $pageIds = $decoded;
        } else {
            $pageIds = preg_split('/\s*,\s*/', $pageIds) ?: [];
        }
    } else {
        $pageIds = [];
    }
}

$pages = sync_contact_form_page_visibility($pdo, $pageIds, (int) $actor['id']);
audit_log((int) $actor['id'], 'update', 'page_section_orders', null, [
    'section' => 'contact_form',
    'page_ids' => array_values(array_map('intval', $pageIds)),
]);

json_ok(['pages' => $pages], 'Contact form page visibility updated');
