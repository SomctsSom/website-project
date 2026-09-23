<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('menus.edit');
auth_verify_csrf($actor);

$input = request_input();
$items = $input['items'] ?? [];
if (!is_array($items) || !$items) {
    json_error('items array is required', 422);
}

$pdo = db();
$pdo->beginTransaction();
try {
    foreach ($items as $item) {
        $id = (int) ($item['id'] ?? 0);
        $sort = (int) ($item['sort_order'] ?? 0);
        if ($id < 1) {
            continue;
        }
        $pdo->prepare('UPDATE menus SET sort_order = :s, updated_at = :u, updated_by = :by WHERE id = :id AND deleted_at IS NULL')
            ->execute([':s' => $sort, ':u' => now_utc(), ':by' => $actor['id'], ':id' => $id]);
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_error('Reorder failed', 500);
}
audit_log((int) $actor['id'], 'reorder', 'menus', null, ['count' => count($items)]);
json_ok(null, 'Menus reordered');
