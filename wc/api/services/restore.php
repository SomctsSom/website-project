<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('services.restore');
auth_verify_csrf($actor);

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid service id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM services WHERE id = :id AND deleted_at IS NOT NULL');
$stmt->execute([':id' => $id]);
if (!$stmt->fetch()) {
    json_error('Deleted service not found', 404);
}
if (!restore_row($pdo, 'services', $id, (int) $actor['id'])) {
    json_error('Restore failed', 500);
}

// Restore page assignments when no active duplicate
$sps = $pdo->prepare('SELECT id, page_id FROM service_pages WHERE service_id = :id AND deleted_at IS NOT NULL');
$sps->execute([':id' => $id]);
foreach ($sps->fetchAll() ?: [] as $row) {
    $dup = $pdo->prepare('SELECT id FROM service_pages WHERE service_id = :s AND page_id = :p AND deleted_at IS NULL LIMIT 1');
    $dup->execute([':s' => $id, ':p' => $row['page_id']]);
    if (!$dup->fetchColumn()) {
        restore_row($pdo, 'service_pages', (int) $row['id'], (int) $actor['id']);
    }
}

// Restore items
$items = $pdo->prepare('SELECT id FROM service_items WHERE service_id = :id AND deleted_at IS NOT NULL');
$items->execute([':id' => $id]);
foreach ($items->fetchAll(PDO::FETCH_COLUMN) ?: [] as $itemId) {
    restore_row($pdo, 'service_items', (int) $itemId, (int) $actor['id']);
}

audit_log((int) $actor['id'], 'restore', 'services', $id);
json_ok(['id' => $id], 'Service restored');
