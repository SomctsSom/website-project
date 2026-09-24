<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('profile_overviews.delete');
auth_verify_csrf($actor);

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid profile overview id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM profile_overviews WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
if (!$stmt->fetchColumn()) {
    json_error('Profile overview not found', 404);
}

$q = $pdo->prepare('SELECT id FROM profile_overview_pages WHERE profile_overview_id = :id AND deleted_at IS NULL');
$q->execute([':id' => $id]);
foreach ($q->fetchAll(PDO::FETCH_COLUMN) ?: [] as $rowId) {
    soft_delete_row($pdo, 'profile_overview_pages', (int) $rowId, (int) $actor['id']);
}

if (!soft_delete_row($pdo, 'profile_overviews', $id, (int) $actor['id'])) {
    json_error('Soft delete failed', 500);
}
audit_log((int) $actor['id'], 'soft_delete', 'profile_overviews', $id);
json_ok(['id' => $id], 'Profile overview soft-deleted');
