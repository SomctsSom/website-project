<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('features.delete');
auth_verify_csrf($actor);

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid feature id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM features WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
if (!$stmt->fetchColumn()) {
    json_error('Feature section not found', 404);
}

$q = $pdo->prepare('SELECT id FROM feature_pages WHERE feature_id = :id AND deleted_at IS NULL');
$q->execute([':id' => $id]);
foreach ($q->fetchAll(PDO::FETCH_COLUMN) ?: [] as $rowId) {
    soft_delete_row($pdo, 'feature_pages', (int) $rowId, (int) $actor['id']);
}
$q2 = $pdo->prepare('SELECT id FROM feature_cards WHERE feature_id = :id AND deleted_at IS NULL');
$q2->execute([':id' => $id]);
foreach ($q2->fetchAll(PDO::FETCH_COLUMN) ?: [] as $rowId) {
    soft_delete_row($pdo, 'feature_cards', (int) $rowId, (int) $actor['id']);
}

if (!soft_delete_row($pdo, 'features', $id, (int) $actor['id'])) {
    json_error('Soft delete failed', 500);
}
audit_log((int) $actor['id'], 'soft_delete', 'features', $id);
json_ok(['id' => $id], 'Feature section soft-deleted');
