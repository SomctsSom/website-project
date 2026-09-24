<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('features.restore');
auth_verify_csrf($actor);

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid feature id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM features WHERE id = :id AND deleted_at IS NOT NULL');
$stmt->execute([':id' => $id]);
if (!$stmt->fetch()) {
    json_error('Deleted feature section not found', 404);
}
if (!restore_row($pdo, 'features', $id, (int) $actor['id'])) {
    json_error('Restore failed', 500);
}

$sps = $pdo->prepare('SELECT id, page_id FROM feature_pages WHERE feature_id = :id AND deleted_at IS NOT NULL');
$sps->execute([':id' => $id]);
foreach ($sps->fetchAll() ?: [] as $row) {
    $dup = $pdo->prepare(
        'SELECT id FROM feature_pages WHERE feature_id = :s AND page_id = :p AND deleted_at IS NULL LIMIT 1'
    );
    $dup->execute([':s' => $id, ':p' => $row['page_id']]);
    if (!$dup->fetchColumn()) {
        restore_row($pdo, 'feature_pages', (int) $row['id'], (int) $actor['id']);
    }
}

$cs = $pdo->prepare('SELECT id FROM feature_cards WHERE feature_id = :id AND deleted_at IS NOT NULL');
$cs->execute([':id' => $id]);
foreach ($cs->fetchAll(PDO::FETCH_COLUMN) ?: [] as $cardId) {
    restore_row($pdo, 'feature_cards', (int) $cardId, (int) $actor['id']);
}

audit_log((int) $actor['id'], 'restore', 'features', $id);
json_ok(['id' => $id], 'Feature section restored');
