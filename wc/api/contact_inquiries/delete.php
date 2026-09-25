<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('contact_inquiries.delete');
auth_verify_csrf($actor);

$id = (int) (array_merge(request_input(), $_POST)['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM contact_inquiries WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
if (!(int) $stmt->fetchColumn()) {
    json_error('Not found', 404);
}
if (!soft_delete_row($pdo, 'contact_inquiries', $id, (int) $actor['id'])) {
    json_error('Delete failed', 500);
}
audit_log((int) $actor['id'], 'soft_delete', 'contact_inquiries', $id);
json_ok(['id' => $id], 'Inquiry deleted');
