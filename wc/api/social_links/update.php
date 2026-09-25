<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('contact_social.edit');
auth_verify_csrf($actor);

$input = array_merge(request_input(), $_POST);
$id = (int) ($input['id'] ?? 0);
if ($id < 1) {
    json_error('Invalid id', 422);
}
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM social_links WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $id]);
$existing = $stmt->fetch();
if (!$existing) {
    json_error('Not found', 404);
}

try {
    $url = sanitize_social_link_url($input['link_url'] ?? $existing['link_url']);
} catch (Throwable $e) {
    json_error($e->getMessage(), 422);
}
if ($url === '') {
    json_error('Link URL is required', 422);
}
$icon = sanitize_feature_icon_class($input['icon_class'] ?? $existing['icon_class']);
$label = trim((string) ($input['label'] ?? ($existing['label'] ?? '')));
$sort = array_key_exists('sort_order', $input) ? (int) $input['sort_order'] : (int) $existing['sort_order'];
$vis = array_key_exists('is_visible', $input) ? to_bool_int($input['is_visible'], 1) : (int) $existing['is_visible'];
$active = array_key_exists('is_active', $input) ? to_bool_int($input['is_active'], 1) : (int) $existing['is_active'];

$pagesJson = (string) ($input['pages_json'] ?? 'null');
$pageAssignments = json_decode($pagesJson, true);

$pdo->beginTransaction();
try {
    $pdo->prepare(
        'UPDATE social_links SET icon_class = :icon, link_url = :url, label = :label, sort_order = :sort,
         is_visible = :vis, is_active = :active, updated_at = :u, updated_by = :by WHERE id = :id'
    )->execute([
        ':icon' => $icon,
        ':url' => $url,
        ':label' => $label !== '' ? mb_substr($label, 0, 120) : null,
        ':sort' => $sort,
        ':vis' => $vis,
        ':active' => $active,
        ':u' => now_utc(),
        ':by' => $actor['id'],
        ':id' => $id,
    ]);
    if (is_array($pageAssignments)) {
        sync_social_link_pages($pdo, $id, $pageAssignments, (int) $actor['id']);
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_error($e->getMessage(), 422);
}

audit_log((int) $actor['id'], 'update', 'social_links', $id);
json_ok(['id' => $id], 'Social link updated');
