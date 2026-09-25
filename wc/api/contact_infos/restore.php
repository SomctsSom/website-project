<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('contact_social.restore');
auth_verify_csrf($actor);

$id = (int) (array_merge(request_input(), $_POST)['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM contact_infos WHERE id = :id AND deleted_at IS NOT NULL');
$stmt->execute([':id' => $id]);
if (!$stmt->fetchColumn()) {
    json_error('Not found', 404);
}
if (!restore_row($pdo, 'contact_infos', $id, (int) $actor['id'])) {
    json_error('Restore failed', 500);
}
audit_log((int) $actor['id'], 'restore', 'contact_infos', $id);
json_ok(['id' => $id], 'Contact info restored');
