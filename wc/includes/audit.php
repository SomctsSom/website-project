<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/helpers.php';

function audit_log(
    ?int $userId,
    string $action,
    string $entityType,
    ?int $entityId = null,
    ?array $details = null
): void {
    try {
        $pdo = db();
        $safe = sanitize_audit_details($details);
        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details_json, ip_address, user_agent, created_at)
             VALUES (:user_id, :action, :entity_type, :entity_id, :details_json, :ip, :ua, :created_at)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':details_json' => $safe === null ? null : json_encode($safe, JSON_UNESCAPED_UNICODE),
            ':ip' => client_ip(),
            ':ua' => client_user_agent(),
            ':created_at' => now_utc(),
        ]);
    } catch (Throwable $e) {
        error_log('audit_log failed: ' . $e->getMessage());
    }
}

function sanitize_audit_details(?array $details): ?array
{
    if ($details === null) {
        return null;
    }
    $blocked = ['password', 'password_hash', 'token', 'csrf', 'session', 'secret', 'file_contents', 'tmp_name'];
    $out = [];
    foreach ($details as $key => $value) {
        $lk = strtolower((string) $key);
        foreach ($blocked as $b) {
            if (str_contains($lk, $b)) {
                continue 2;
            }
        }
        if (is_array($value)) {
            $out[$key] = sanitize_audit_details($value);
        } elseif (is_scalar($value) || $value === null) {
            $out[$key] = $value;
        } else {
            $out[$key] = (string) $value;
        }
    }
    return $out;
}
