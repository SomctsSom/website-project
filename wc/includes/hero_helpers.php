<?php
declare(strict_types=1);

function hero_size_presets(): array
{
    return [
        'sm' => 'Small',
        'md' => 'Medium',
        'lg' => 'Large',
        'xl' => 'Extra large',
    ];
}

function normalize_hero_size_preset(?string $value, string $default = 'md'): string
{
    $value = strtolower(trim((string) $value));
    return array_key_exists($value, hero_size_presets()) ? $value : $default;
}

function get_hero_size_preset(?PDO $pdo = null): string
{
    $pdo = $pdo ?? db();
    try {
        $stmt = $pdo->prepare(
            "SELECT setting_value FROM site_settings WHERE setting_key = 'hero_size_preset' LIMIT 1"
        );
        $stmt->execute();
        $value = $stmt->fetchColumn();
        if ($value !== false && $value !== null) {
            return normalize_hero_size_preset((string) $value);
        }
    } catch (Throwable $e) {
        // Table may not exist yet during early migrate
    }
    return 'md';
}

function set_hero_size_preset(PDO $pdo, string $preset, ?int $actorId = null): string
{
    $preset = normalize_hero_size_preset($preset);
    $pdo->prepare(
        "INSERT INTO site_settings (setting_key, setting_value, updated_at, updated_by)
         VALUES ('hero_size_preset', :v, :u, :by)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value),
           updated_at = VALUES(updated_at), updated_by = VALUES(updated_by)"
    )->execute([
        ':v' => $preset,
        ':u' => now_utc(),
        ':by' => $actorId,
    ]);
    return $preset;
}

/**
 * @param array<int,array<string,mixed>> $assignments
 */
function sync_hero_pages(PDO $pdo, int $heroId, array $assignments, int $actorId): void
{
    $seen = [];
    foreach ($assignments as $a) {
        $pageId = (int) ($a['page_id'] ?? 0);
        if ($pageId < 1 || isset($seen[$pageId])) {
            continue;
        }
        $seen[$pageId] = true;
        $p = $pdo->prepare("SELECT id FROM pages WHERE id = :id AND page_type = 'website' AND deleted_at IS NULL");
        $p->execute([':id' => $pageId]);
        if (!$p->fetchColumn()) {
            throw new RuntimeException("Website page {$pageId} not found");
        }
        $isFeatured = to_bool_int($a['is_featured'] ?? false);
        $isActive = to_bool_int($a['is_active'] ?? true, 1);
        $sort = (int) ($a['sort_order'] ?? 0);
        if ($isFeatured === 1 && $isActive !== 1) {
            throw new RuntimeException('A page may feature a hero only when the assignment is active');
        }

        $existing = $pdo->prepare('SELECT id FROM hero_pages WHERE hero_id = :h AND page_id = :p AND deleted_at IS NULL LIMIT 1');
        $existing->execute([':h' => $heroId, ':p' => $pageId]);
        $exId = $existing->fetchColumn();
        if ($exId) {
            $pdo->prepare(
                'UPDATE hero_pages SET is_featured = :f, is_active = :a, sort_order = :s, updated_at = :u, updated_by = :by WHERE id = :id'
            )->execute([':f' => $isFeatured, ':a' => $isActive, ':s' => $sort, ':u' => now_utc(), ':by' => $actorId, ':id' => $exId]);
            continue;
        }
        $old = $pdo->prepare('SELECT id FROM hero_pages WHERE hero_id = :h AND page_id = :p AND deleted_at IS NOT NULL ORDER BY id DESC LIMIT 1');
        $old->execute([':h' => $heroId, ':p' => $pageId]);
        $oldId = $old->fetchColumn();
        if ($oldId) {
            restore_row($pdo, 'hero_pages', (int) $oldId, $actorId);
            $pdo->prepare(
                'UPDATE hero_pages SET is_featured = :f, is_active = :a, sort_order = :s, updated_at = :u, updated_by = :by WHERE id = :id'
            )->execute([':f' => $isFeatured, ':a' => $isActive, ':s' => $sort, ':u' => now_utc(), ':by' => $actorId, ':id' => $oldId]);
        } else {
            $pdo->prepare(
                'INSERT INTO hero_pages (hero_id, page_id, is_featured, is_active, sort_order, created_at, created_by)
                 VALUES (:h, :p, :f, :a, :s, :c, :by)'
            )->execute([
                ':h' => $heroId, ':p' => $pageId, ':f' => $isFeatured, ':a' => $isActive,
                ':s' => $sort, ':c' => now_utc(), ':by' => $actorId,
            ]);
        }
    }

    $cur = $pdo->prepare('SELECT id, page_id FROM hero_pages WHERE hero_id = :h AND deleted_at IS NULL');
    $cur->execute([':h' => $heroId]);
    foreach ($cur->fetchAll() ?: [] as $row) {
        if (!isset($seen[(int) $row['page_id']])) {
            soft_delete_row($pdo, 'hero_pages', (int) $row['id'], $actorId);
        }
    }
}
