<?php
declare(strict_types=1);

require_method('GET');

$menuType = (string) (request_input()['menu_type'] ?? 'website');
if ($menuType !== 'website') {
    json_error('Only website menus are public', 403);
}

$pdo = db();
$tree = menu_tree('website', true);

// Attach only active pages
$enrich = function (array $items) use (&$enrich, $pdo): array {
    $out = [];
    foreach ($items as $item) {
        if ((int) $item['is_active'] !== 1 || $item['deleted_at'] !== null) {
            continue;
        }
        $pages = $pdo->prepare(
            "SELECT p.id, p.title, p.slug, p.template_key
             FROM menu_pages mp
             JOIN pages p ON p.id = mp.page_id
             WHERE mp.menu_id = :m AND mp.deleted_at IS NULL
               AND p.deleted_at IS NULL AND p.is_active = 1 AND p.page_type = 'website'
             ORDER BY mp.sort_order, p.title"
        );
        $pages->execute([':m' => $item['id']]);
        $item['pages'] = $pages->fetchAll() ?: [];
        $item['children'] = $enrich($item['children'] ?? []);
        $out[] = [
            'id' => (int) $item['id'],
            'title' => $item['title'],
            'url' => $item['url'],
            'icon' => $item['icon'],
            'sort_order' => (int) $item['sort_order'],
            'pages' => $item['pages'],
            'children' => $item['children'],
        ];
    }
    return $out;
};

json_ok(['items' => $enrich($tree)]);
