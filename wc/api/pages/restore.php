<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('pages.restore');
auth_verify_csrf($actor);

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid page id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM pages WHERE id = :id AND deleted_at IS NOT NULL');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
if (!$row) {
    json_error('Deleted page not found', 404);
}
$dup = $pdo->prepare('SELECT id FROM pages WHERE type_slug_active = :k LIMIT 1');
$dup->execute([':k' => $row['page_type'] . ':' . $row['slug']]);
if ($dup->fetchColumn()) {
    json_error('Cannot restore: slug already in use for this page type', 422);
}
if (!restore_row($pdo, 'pages', $id, (int) $actor['id'])) {
    json_error('Restore failed', 500);
}
audit_log((int) $actor['id'], 'restore', 'pages', $id);
json_ok(['id' => $id], 'Page restored');
