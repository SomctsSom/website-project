<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('contact_social.delete');
auth_verify_csrf($actor);

$id = (int) (array_merge(request_input(), $_POST)['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM social_links WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
if (!$stmt->fetchColumn()) {
    json_error('Not found', 404);
}
if (!soft_delete_row($pdo, 'social_links', $id, (int) $actor['id'])) {
    json_error('Delete failed', 500);
}
$pages = $pdo->prepare('SELECT id FROM social_link_pages WHERE social_link_id = :id AND deleted_at IS NULL');
$pages->execute([':id' => $id]);
foreach ($pages->fetchAll() ?: [] as $row) {
    soft_delete_row($pdo, 'social_link_pages', (int) $row['id'], (int) $actor['id']);
}
audit_log((int) $actor['id'], 'soft_delete', 'social_links', $id);
json_ok(['id' => $id], 'Social link deleted');
