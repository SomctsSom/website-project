<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('vision_missions.delete');
auth_verify_csrf($actor);

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid vision/mission id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM vision_missions WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
if (!$stmt->fetchColumn()) {
    json_error('Vision & Mission not found', 404);
}

$q = $pdo->prepare('SELECT id FROM vision_mission_pages WHERE vision_mission_id = :id AND deleted_at IS NULL');
$q->execute([':id' => $id]);
foreach ($q->fetchAll(PDO::FETCH_COLUMN) ?: [] as $rowId) {
    soft_delete_row($pdo, 'vision_mission_pages', (int) $rowId, (int) $actor['id']);
}

if (!soft_delete_row($pdo, 'vision_missions', $id, (int) $actor['id'])) {
    json_error('Soft delete failed', 500);
}
audit_log((int) $actor['id'], 'soft_delete', 'vision_missions', $id);
json_ok(['id' => $id], 'Vision & Mission soft-deleted');
