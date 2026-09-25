<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('contact_inquiries.edit');
auth_verify_csrf($actor);

$input = array_merge(request_input(), $_POST);
$id = (int) ($input['id'] ?? 0);
$status = trim((string) ($input['status'] ?? ''));
if ($id < 1) {
    json_error('Invalid id', 422);
}
if (!in_array($status, contact_inquiry_statuses(), true)) {
    json_error('Invalid status', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM contact_inquiries WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
if (!(int) $stmt->fetchColumn()) {
    json_error('Not found', 404);
}
$pdo->prepare(
    'UPDATE contact_inquiries SET status = :status, updated_at = :u, updated_by = :by WHERE id = :id'
)->execute([
    ':status' => $status,
    ':u' => now_utc(),
    ':by' => $actor['id'],
    ':id' => $id,
]);
audit_log((int) $actor['id'], 'update', 'contact_inquiries', $id, ['status' => $status]);
json_ok(['id' => $id, 'status' => $status], 'Status updated');
