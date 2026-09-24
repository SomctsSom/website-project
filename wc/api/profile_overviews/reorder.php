<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('profile_overviews.edit');
auth_verify_csrf($actor);

$items = request_input()['items'] ?? [];
if (!is_array($items) || !$items) {
    json_error('items array is required', 422);
}
$pdo = db();
foreach ($items as $item) {
    $id = (int) ($item['id'] ?? 0);
    $sort = (int) ($item['sort_order'] ?? 0);
    if ($id < 1) {
        continue;
    }
    $pdo->prepare(
        'UPDATE profile_overviews SET sort_order = :s, updated_at = :u, updated_by = :by WHERE id = :id AND deleted_at IS NULL'
    )->execute([':s' => $sort, ':u' => now_utc(), ':by' => $actor['id'], ':id' => $id]);
}
audit_log((int) $actor['id'], 'reorder', 'profile_overviews', null, ['count' => count($items)]);
json_ok(null, 'Profile overviews reordered');
