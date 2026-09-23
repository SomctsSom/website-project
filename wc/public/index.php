<?php
declare(strict_types=1);

/**
 * Website Core front controller.
 * Document root should point here. Routes /api/* and /media/*.
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . trim($path, '/');
if ($path === '/') {
    $path = '/health';
}

// Strip trailing slash except root
if ($path !== '/' && str_ends_with($path, '/')) {
    $path = rtrim($path, '/');
}

try {
    if ($path === '/health') {
        json_ok(['status' => 'ok', 'app' => app_config('name'), 'time' => now_utc()]);
    }

    if (preg_match('#^/media/(.+)$#', $path, $m) === 1) {
        require dirname(__DIR__) . '/api/public/media.php';
        exit;
    }

    $apiMap = [
        // Auth
        'POST /api/auth/login' => 'auth/login.php',
        'POST /api/auth/logout' => 'auth/logout.php',
        'GET /api/auth/me' => 'auth/me.php',
        'POST /api/auth/change-password' => 'auth/change_password.php',

        // Users
        'GET /api/users' => 'users/list.php',
        'GET /api/users/view' => 'users/view.php',
        'POST /api/users/create' => 'users/create.php',
        'POST /api/users/update' => 'users/update.php',
        'POST /api/users/delete' => 'users/delete.php',
        'POST /api/users/restore' => 'users/restore.php',

        // Roles
        'GET /api/roles' => 'roles/list.php',
        'GET /api/roles/view' => 'roles/view.php',
        'POST /api/roles/create' => 'roles/create.php',
        'POST /api/roles/update' => 'roles/update.php',
        'POST /api/roles/delete' => 'roles/delete.php',
        'POST /api/roles/restore' => 'roles/restore.php',
        'POST /api/roles/assign-permissions' => 'roles/assign_permissions.php',

        // Permissions
        'GET /api/permissions' => 'permissions/list.php',
        'POST /api/permissions/create' => 'permissions/create.php',
        'POST /api/permissions/update' => 'permissions/update.php',
        'POST /api/permissions/delete' => 'permissions/delete.php',
        'POST /api/permissions/restore' => 'permissions/restore.php',

        // Menus
        'GET /api/menus' => 'menus/list.php',
        'GET /api/menus/view' => 'menus/view.php',
        'POST /api/menus/create' => 'menus/create.php',
        'POST /api/menus/update' => 'menus/update.php',
        'POST /api/menus/delete' => 'menus/delete.php',
        'POST /api/menus/restore' => 'menus/restore.php',
        'POST /api/menus/reorder' => 'menus/reorder.php',
        'POST /api/menus/assign-pages' => 'menus/assign_pages.php',

        // Pages
        'GET /api/pages' => 'pages/list.php',
        'GET /api/pages/view' => 'pages/view.php',
        'POST /api/pages/create' => 'pages/create.php',
        'POST /api/pages/update' => 'pages/update.php',
        'POST /api/pages/delete' => 'pages/delete.php',
        'POST /api/pages/restore' => 'pages/restore.php',

        // Hero
        'GET /api/hero' => 'hero/list.php',
        'GET /api/hero/view' => 'hero/view.php',
        'POST /api/hero/create' => 'hero/create.php',
        'POST /api/hero/update' => 'hero/update.php',
        'POST /api/hero/delete' => 'hero/delete.php',
        'POST /api/hero/restore' => 'hero/restore.php',
        'POST /api/hero/reorder' => 'hero/reorder.php',
        'GET /api/hero/size' => 'hero/size.php',
        'POST /api/hero/size' => 'hero/size.php',

        // Services
        'GET /api/services' => 'services/list.php',
        'GET /api/services/view' => 'services/view.php',
        'POST /api/services/create' => 'services/create.php',
        'POST /api/services/update' => 'services/update.php',
        'POST /api/services/delete' => 'services/delete.php',
        'POST /api/services/restore' => 'services/restore.php',
        'POST /api/services/reorder' => 'services/reorder.php',

        // Trash & Audit
        'GET /api/trash' => 'trash/list.php',
        'POST /api/trash/restore' => 'trash/restore.php',
        'GET /api/audit' => 'audit/list.php',

        // Navigation for admin UI
        'GET /api/admin/navigation' => 'menus/admin_navigation.php',

        // Public (no auth)
        'GET /api/public/menus' => 'public/menus.php',
        'GET /api/public/pages' => 'public/pages.php',
        'GET /api/public/page' => 'public/page.php',
        'GET /api/public/hero' => 'public/hero.php',
        'GET /api/public/services' => 'public/services.php',
    ];

    $routeKey = request_method() . ' ' . $path;
    if (!isset($apiMap[$routeKey])) {
        // Also allow /api/users/{id} style via query? we use query params
        json_error('Not found', 404);
    }

    $file = dirname(__DIR__) . '/api/' . $apiMap[$routeKey];
    if (!is_file($file)) {
        json_error('Endpoint missing', 500);
    }
    require $file;
} catch (Throwable $e) {
    error_log('WC error: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    $msg = env_bool('APP_DEBUG', false) ? $e->getMessage() : 'Internal server error';
    json_error($msg, 500);
}
