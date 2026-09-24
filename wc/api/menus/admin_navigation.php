<?php
declare(strict_types=1);

/**
 * Filtered admin navigation tree for the logged-in user (permission-aware).
 */
require_method('GET');
$user = auth_require_user();

$pdo = db();
$tree = menu_tree('admin', true);

// Map menu leaf titles/urls to required permissions
$permMap = [
    '/dashboard' => 'dashboard.view',
    '/users' => 'users.view',
    '/roles' => 'roles.view',
    '/permissions' => 'permissions.view',
    '/website-menus' => 'menus.view',
    '/admin-menus' => 'menus.view',
    '/website-pages' => 'pages.view',
    '/admin-pages' => 'pages.view',
    '/hero' => 'hero.view',
    '/services' => 'services.view',
    '/profile-overviews' => 'profile_overviews.view',
    '/vision-missions' => 'vision_missions.view',
    '/features' => 'features.view',
    '/navbar-colors' => 'navbar_colors.view',
    '/trash' => 'trash.view',
    '/audit-logs' => 'audit.view',
];

$filter = function (array $items) use (&$filter, $user, $permMap): array {
    $out = [];
    foreach ($items as $item) {
        $children = $filter($item['children'] ?? []);
        $url = (string) ($item['url'] ?? '');
        $needed = $permMap[$url] ?? null;
        $allowed = $needed === null ? true : auth_can($user, $needed);
        // Group parents: keep if any child remains or direct url allowed
        if ($children) {
            $item['children'] = $children;
            $out[] = $item;
            continue;
        }
        if ($url !== '' && $allowed && (int) $item['is_active'] === 1) {
            $item['children'] = [];
            $out[] = $item;
        } elseif ($url === '' && $allowed) {
            // empty group with no children — skip
            continue;
        }
    }
    return $out;
};

json_ok([
    'items' => $filter($tree),
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'role_name' => $user['role_name'],
        'permissions' => auth_permissions((int) $user['id']),
        'csrf_token' => $user['csrf_token'],
    ],
]);
