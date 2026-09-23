<?php
declare(strict_types=1);

function wa_load_env(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    $path = dirname(__DIR__) . '/.env';
    if (!is_readable($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
        putenv(trim($k) . '=' . trim($v));
    }
}

function wa_env(string $key, mixed $default = null): mixed
{
    wa_load_env();
    $v = $_ENV[$key] ?? getenv($key);
    return ($v === false || $v === null || $v === '') ? $default : $v;
}

function wa_e(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function wa_base_url(): string
{
    return rtrim((string) wa_env('WA_BASE_URL', ''), '/');
}

function wa_url(string $path = '/'): string
{
    $path = '/' . ltrim($path, '/');
    return wa_base_url() . ($path === '/' ? '' : $path);
}

function wa_redirect(string $path): never
{
    header('Location: ' . wa_url($path));
    exit;
}

function wa_flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function wa_consume_flash(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

function wa_start_session(): void
{
    wa_load_env();
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name((string) wa_env('SESSION_NAME', 'wms_wa_session'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => in_array(strtolower((string) wa_env('COOKIE_SECURE', '0')), ['1', 'true'], true),
        'httponly' => true,
        'samesite' => (string) wa_env('COOKIE_SAMESITE', 'Lax'),
    ]);
    session_start();
}
