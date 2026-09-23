<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

/**
 * Return a shared PDO connection. Only Website Core may use this.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    wms_load_env();

    $host = (string) env('DB_HOST', '127.0.0.1');
    $port = (string) env('DB_PORT', '3306');
    $name = (string) env('DB_DATABASE', 'wms_core');
    $user = (string) env('DB_USERNAME', 'root');
    $pass = (string) env('DB_PASSWORD', '');
    $charset = (string) env('DB_CHARSET', 'utf8mb4');

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec("SET time_zone = '+00:00'");

    return $pdo;
}
