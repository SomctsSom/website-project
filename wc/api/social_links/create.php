<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('contact_social.create');
auth_verify_csrf($actor);

$input = array_merge(request_input(), $_POST);
$itemsJson = (string) ($input['items_json'] ?? '[]');
$items = json_decode($itemsJson, true);
if (!is_array($items) || $items === []) {
    // single-item fallback
    $items = [[
        'icon_class' => $input['icon_class'] ?? 'fab fa-link',
        'link_url' => $input['link_url'] ?? '',
        'label' => $input['label'] ?? '',
        'sort_order' => $input['sort_order'] ?? 0,
        'is_visible' => $input['is_visible'] ?? 1,
        'is_active' => $input['is_active'] ?? 1,
    ]];
}

$pagesJson = (string) ($input['pages_json'] ?? '[]');
$pageAssignments = json_decode($pagesJson, true);
if (!is_array($pageAssignments)) {
    $pageAssignments = [];
}

$pdo = db();
$pdo->beginTransaction();
$createdIds = [];
try {
    $ins = $pdo->prepare(
        'INSERT INTO social_links (icon_class, link_url, label, sort_order, is_visible, is_active, created_at, created_by)
         VALUES (:icon, :url, :label, :sort, :vis, :active, :created, :by)'
    );
    $sortBase = 10;
    foreach ($items as $item) {
        $url = sanitize_social_link_url($item['link_url'] ?? '');
        if ($url === '') {
            continue;
        }
        $icon = sanitize_feature_icon_class($item['icon_class'] ?? 'fab fa-link');
        $label = trim((string) ($item['label'] ?? ''));
        $sort = isset($item['sort_order']) ? (int) $item['sort_order'] : $sortBase;
        $vis = to_bool_int($item['is_visible'] ?? true, 1);
        $active = to_bool_int($item['is_active'] ?? true, 1);
        $ins->execute([
            ':icon' => $icon,
            ':url' => $url,
            ':label' => $label !== '' ? mb_substr($label, 0, 120) : null,
            ':sort' => $sort,
            ':vis' => $vis,
            ':active' => $active,
            ':created' => now_utc(),
            ':by' => $actor['id'],
        ]);
        $id = (int) $pdo->lastInsertId();
        $createdIds[] = $id;
        sync_social_link_pages($pdo, $id, $pageAssignments, (int) $actor['id']);
        $sortBase += 10;
    }
    if ($createdIds === []) {
        throw new RuntimeException('At least one social link with a valid URL is required');
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_error($e->getMessage(), 422);
}

audit_log((int) $actor['id'], 'create', 'social_links', null, ['ids' => $createdIds, 'count' => count($createdIds)]);
json_ok(['ids' => $createdIds, 'count' => count($createdIds)], 'Social links created', 201);
