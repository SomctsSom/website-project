<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('pages.delete');
auth_verify_csrf($actor);

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid page id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM pages WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
if (!$stmt->fetchColumn()) {
    json_error('Page not found', 404);
}

$mps = $pdo->prepare('SELECT id FROM menu_pages WHERE page_id = :id AND deleted_at IS NULL');
$mps->execute([':id' => $id]);
foreach ($mps->fetchAll(PDO::FETCH_COLUMN) ?: [] as $mpId) {
    soft_delete_row($pdo, 'menu_pages', (int) $mpId, (int) $actor['id']);
}
$hps = $pdo->prepare('SELECT id FROM hero_pages WHERE page_id = :id AND deleted_at IS NULL');
$hps->execute([':id' => $id]);
foreach ($hps->fetchAll(PDO::FETCH_COLUMN) ?: [] as $hpId) {
    soft_delete_row($pdo, 'hero_pages', (int) $hpId, (int) $actor['id']);
}

if (!soft_delete_row($pdo, 'pages', $id, (int) $actor['id'])) {
    json_error('Soft delete failed', 500);
}
audit_log((int) $actor['id'], 'soft_delete', 'pages', $id);
json_ok(['id' => $id], 'Page soft-deleted');
