<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/helpers.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . trim($path, '/');
if ($path === '//') {
    $path = '/';
}
if ($path !== '/' && str_ends_with($path, '/')) {
    $path = rtrim($path, '/');
}

if (str_starts_with($path, '/assets/')) {
    $file = __DIR__ . $path;
    if (is_file($file)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $types = ['css' => 'text/css', 'js' => 'application/javascript'];
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        readfile($file);
        exit;
    }
    http_response_code(404);
    exit;
}

$slug = $path === '/' ? 'home' : ltrim($path, '/');
// Prevent reserved overrides
$reserved = ['api', 'admin', 'assets', 'media', 'login'];
if (in_array(explode('/', $slug)[0], $reserved, true)) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

require dirname(__DIR__) . '/pages/page.php';
