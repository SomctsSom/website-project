<?php
declare(strict_types=1);

require_method('POST');
$actor = auth_require_permission('menus.edit');
auth_verify_csrf($actor);

$input = request_input();
$menuId = (int) ($input['menu_id'] ?? 0);
$pageIds = $input['page_ids'] ?? [];
if (!is_array($pageIds)) {
    json_error('page_ids must be an array', 422);
}
$pageIds = array_values(array_unique(array_map('intval', $pageIds)));
if ($menuId < 1) {
    json_error('Invalid menu id', 422);
}

$pdo = db();
$menu = $pdo->prepare('SELECT * FROM menus WHERE id = :id AND deleted_at IS NULL');
$menu->execute([':id' => $menuId]);
$menuRow = $menu->fetch();
if (!$menuRow) {
    json_error('Menu not found', 404);
}

$pdo->beginTransaction();
try {
    $current = $pdo->prepare('SELECT id, page_id FROM menu_pages WHERE menu_id = :m AND deleted_at IS NULL');
    $current->execute([':m' => $menuId]);
    $map = [];
    foreach ($current->fetchAll() ?: [] as $row) {
        $map[(int) $row['page_id']] = (int) $row['id'];
    }
    foreach ($map as $pid => $mpId) {
        if (!in_array($pid, $pageIds, true)) {
            soft_delete_row($pdo, 'menu_pages', $mpId, (int) $actor['id']);
        }
    }
    $sort = 0;
    foreach ($pageIds as $pid) {
        if ($pid < 1) {
            continue;
        }
        $p = $pdo->prepare('SELECT id, page_type FROM pages WHERE id = :id AND deleted_at IS NULL');
        $p->execute([':id' => $pid]);
        $page = $p->fetch();
        if (!$page) {
            throw new RuntimeException("Page {$pid} not found");
        }
        if ($page['page_type'] !== $menuRow['menu_type']) {
            throw new RuntimeException('Menu and page types must match (website↔website, admin↔admin)');
        }
        if (isset($map[$pid])) {
            $pdo->prepare('UPDATE menu_pages SET sort_order = :s, updated_at = :u, updated_by = :by WHERE id = :id')
                ->execute([':s' => $sort++, ':u' => now_utc(), ':by' => $actor['id'], ':id' => $map[$pid]]);
            continue;
        }
        $old = $pdo->prepare('SELECT id FROM menu_pages WHERE menu_id = :m AND page_id = :p AND deleted_at IS NOT NULL ORDER BY id DESC LIMIT 1');
        $old->execute([':m' => $menuId, ':p' => $pid]);
        $oldId = $old->fetchColumn();
        if ($oldId) {
            restore_row($pdo, 'menu_pages', (int) $oldId, (int) $actor['id']);
            $pdo->prepare('UPDATE menu_pages SET sort_order = :s WHERE id = :id')->execute([':s' => $sort++, ':id' => $oldId]);
        } else {
            $pdo->prepare(
                'INSERT INTO menu_pages (menu_id, page_id, sort_order, created_at, created_by) VALUES (:m, :p, :s, :c, :by)'
            )->execute([':m' => $menuId, ':p' => $pid, ':s' => $sort++, ':c' => now_utc(), ':by' => $actor['id']]);
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_error($e->getMessage(), 422);
}

audit_log((int) $actor['id'], 'assign_pages', 'menus', $menuId, ['page_ids' => $pageIds]);
json_ok(['menu_id' => $menuId], 'Pages assigned to menu');
