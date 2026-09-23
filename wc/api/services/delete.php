<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('services.delete');
auth_verify_csrf($actor);

$id = (int) (request_input()['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid service id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM services WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
if (!$stmt->fetchColumn()) {
    json_error('Service not found', 404);
}

foreach (['service_pages', 'service_items'] as $table) {
    $col = $table === 'service_pages' || $table === 'service_items' ? 'service_id' : 'service_id';
    $q = $pdo->prepare("SELECT id FROM {$table} WHERE service_id = :id AND deleted_at IS NULL");
    $q->execute([':id' => $id]);
    foreach ($q->fetchAll(PDO::FETCH_COLUMN) ?: [] as $rowId) {
        soft_delete_row($pdo, $table, (int) $rowId, (int) $actor['id']);
    }
}

if (!soft_delete_row($pdo, 'services', $id, (int) $actor['id'])) {
    json_error('Soft delete failed', 500);
}
audit_log((int) $actor['id'], 'soft_delete', 'services', $id);
json_ok(['id' => $id], 'Service soft-deleted');
