<?php
declare(strict_types=1);

require_method('GET');
auth_require_permission('menus.view');

$input = request_input();
$menuType = (string) ($input['menu_type'] ?? 'website');
if (!in_array($menuType, ['website', 'admin'], true)) {
    json_error('Invalid menu_type', 422);
}
$tree = ((string) ($input['tree'] ?? '1')) !== '0';
$trashed = ((string) ($input['trashed'] ?? '')) === '1';

$pdo = db();
if ($trashed) {
    $stmt = $pdo->prepare('SELECT * FROM menus WHERE menu_type = :t AND deleted_at IS NOT NULL ORDER BY deleted_at DESC');
    $stmt->execute([':t' => $menuType]);
    json_ok(['items' => $stmt->fetchAll() ?: []]);
}

if ($tree) {
    json_ok(['menu_type' => $menuType, 'items' => menu_tree($menuType, false)]);
}

$q = trim((string) ($input['q'] ?? ''));
$sql = 'SELECT * FROM menus WHERE menu_type = :t AND deleted_at IS NULL';
$params = [':t' => $menuType];
if ($q !== '') {
    $sql .= ' AND title LIKE :q';
    $params[':q'] = '%' . $q . '%';
}
$sql .= ' ORDER BY sort_order ASC, id ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
json_ok(['items' => $stmt->fetchAll() ?: []]);
