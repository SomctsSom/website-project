<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/helpers.php';

/**
 * Menu helpers: cycle prevention and depth limits.
 */

function menu_find(int $id): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM menus WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function menu_would_create_cycle(int $menuId, ?int $newParentId): bool
{
    if ($newParentId === null) {
        return false;
    }
    if ($newParentId === $menuId) {
        return true;
    }
    $pdo = db();
    $current = $newParentId;
    $guard = 0;
    while ($current !== null && $guard < 50) {
        if ($current === $menuId) {
            return true;
        }
        $stmt = $pdo->prepare('SELECT parent_menu_id FROM menus WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute([':id' => $current]);
        $parent = $stmt->fetchColumn();
        $current = $parent === false || $parent === null ? null : (int) $parent;
        $guard++;
    }
    return false;
}

function menu_depth_of(?int $parentId): int
{
    if ($parentId === null) {
        return 0;
    }
    $pdo = db();
    $depth = 0;
    $current = $parentId;
    $guard = 0;
    while ($current !== null && $guard < 50) {
        $depth++;
        $stmt = $pdo->prepare('SELECT parent_menu_id FROM menus WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute([':id' => $current]);
        $parent = $stmt->fetchColumn();
        $current = $parent === false || $parent === null ? null : (int) $parent;
        $guard++;
    }
    return $depth;
}

function menu_validate_parent(string $menuType, ?int $parentId): ?string
{
    if ($parentId === null) {
        return null;
    }
    $parent = menu_find($parentId);
    if ($parent === null || $parent['deleted_at'] !== null) {
        return 'Parent menu not found.';
    }
    if ($parent['menu_type'] !== $menuType) {
        return 'Parent menu must match menu type.';
    }
    $max = (int) app_config('menu_max_depth', 3);
    if (menu_depth_of($parentId) + 1 >= $max) {
        return "Menu nesting cannot exceed depth {$max}.";
    }
    return null;
}

function menu_tree(string $menuType, bool $activeOnly = false): array
{
    $pdo = db();
    $sql = 'SELECT * FROM menus WHERE menu_type = :type AND deleted_at IS NULL';
    if ($activeOnly) {
        $sql .= ' AND is_active = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':type' => $menuType]);
    $rows = $stmt->fetchAll() ?: [];

    $byParent = [];
    foreach ($rows as $row) {
        $pid = $row['parent_menu_id'] === null ? 0 : (int) $row['parent_menu_id'];
        $byParent[$pid][] = $row;
    }

    $build = function (int $parentId) use (&$build, &$byParent): array {
        $items = $byParent[$parentId] ?? [];
        $out = [];
        foreach ($items as $item) {
            $item['children'] = $build((int) $item['id']);
            $out[] = $item;
        }
        return $out;
    };

    return $build(0);
}
