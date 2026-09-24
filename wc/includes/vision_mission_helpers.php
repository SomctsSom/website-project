<?php
declare(strict_types=1);

/**
 * @return list<string>
 */
function vision_mission_types(): array
{
    return ['vision', 'mission'];
}

function normalize_vision_mission_type(mixed $type): string
{
    $type = strtolower(trim((string) $type));
    return in_array($type, vision_mission_types(), true) ? $type : 'vision';
}

/**
 * @param array<int,array<string,mixed>> $assignments
 */
function sync_vision_mission_pages(PDO $pdo, int $visionId, array $assignments, int $actorId): void
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
            throw new RuntimeException('A page may feature a vision/mission only when the assignment is active');
        }

        $existing = $pdo->prepare(
            'SELECT id FROM vision_mission_pages WHERE vision_mission_id = :s AND page_id = :p AND deleted_at IS NULL LIMIT 1'
        );
        $existing->execute([':s' => $visionId, ':p' => $pageId]);
        $exId = $existing->fetchColumn();
        if ($exId) {
            $pdo->prepare(
                'UPDATE vision_mission_pages SET is_featured = :f, is_active = :a, sort_order = :s, updated_at = :u, updated_by = :by WHERE id = :id'
            )->execute([':f' => $isFeatured, ':a' => $isActive, ':s' => $sort, ':u' => now_utc(), ':by' => $actorId, ':id' => $exId]);
            continue;
        }
        $old = $pdo->prepare(
            'SELECT id FROM vision_mission_pages WHERE vision_mission_id = :s AND page_id = :p AND deleted_at IS NOT NULL ORDER BY id DESC LIMIT 1'
        );
        $old->execute([':s' => $visionId, ':p' => $pageId]);
        $oldId = $old->fetchColumn();
        if ($oldId) {
            restore_row($pdo, 'vision_mission_pages', (int) $oldId, $actorId);
            $pdo->prepare(
                'UPDATE vision_mission_pages SET is_featured = :f, is_active = :a, sort_order = :s, updated_at = :u, updated_by = :by WHERE id = :id'
            )->execute([':f' => $isFeatured, ':a' => $isActive, ':s' => $sort, ':u' => now_utc(), ':by' => $actorId, ':id' => $oldId]);
        } else {
            $pdo->prepare(
                'INSERT INTO vision_mission_pages (vision_mission_id, page_id, is_featured, is_active, sort_order, created_at, created_by)
                 VALUES (:s, :p, :f, :a, :sort, :c, :by)'
            )->execute([
                ':s' => $visionId, ':p' => $pageId, ':f' => $isFeatured, ':a' => $isActive,
                ':sort' => $sort, ':c' => now_utc(), ':by' => $actorId,
            ]);
        }
    }

    $cur = $pdo->prepare('SELECT id, page_id FROM vision_mission_pages WHERE vision_mission_id = :s AND deleted_at IS NULL');
    $cur->execute([':s' => $visionId]);
    foreach ($cur->fetchAll() ?: [] as $row) {
        if (!isset($seen[(int) $row['page_id']])) {
            soft_delete_row($pdo, 'vision_mission_pages', (int) $row['id'], $actorId);
        }
    }
}

/**
 * Allowlist sanitize for vision/mission rich body HTML.
 */
function sanitize_vision_mission_html(?string $html): string
{
    $html = trim((string) $html);
    if ($html === '') {
        return '';
    }
    if (!preg_match('/<[a-z][\s\S]*>/i', $html)) {
        $parts = preg_split("/\R{2,}/", $html) ?: [];
        $paras = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $paras[] = $part;
            }
        }
        if (!$paras) {
            $paras = [preg_replace("/\R+/", ' ', $html) ?: $html];
        }
        $html = '';
        foreach ($paras as $p) {
            $html .= '<p>' . htmlspecialchars($p, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
        }
    } else {
        $html = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
    }

    $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><a><h2><h3><h4><blockquote><span>';
    $html = strip_tags($html, $allowed);
    $html = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html) ?? $html;
    $html = preg_replace('/\shref\s*=\s*("|\')\s*javascript:[^"\']*\1/i', ' href="#"', $html) ?? $html;
    $html = preg_replace('/\s(style|class)\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html) ?? $html;
    return trim($html);
}

function vision_mission_attach_media(array $row): array
{
    $row['image_url'] = !empty($row['image_path'])
        ? secure_public_media_url((string) $row['image_path']) : null;
    $rawBody = (string) ($row['body'] ?? '');
    $row['body_html'] = sanitize_vision_mission_html($rawBody);
    $row['statement_type'] = normalize_vision_mission_type($row['statement_type'] ?? 'vision');
    $row['statement_type_label'] = $row['statement_type'] === 'mission' ? 'Mission' : 'Vision';
    return $row;
}
