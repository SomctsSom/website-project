<?php
declare(strict_types=1);

/**
 * @param array<int,array<string,mixed>> $assignments
 */
function sync_profile_overview_pages(PDO $pdo, int $profileId, array $assignments, int $actorId): void
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
            throw new RuntimeException('A page may feature a profile overview only when the assignment is active');
        }

        $existing = $pdo->prepare(
            'SELECT id FROM profile_overview_pages WHERE profile_overview_id = :s AND page_id = :p AND deleted_at IS NULL LIMIT 1'
        );
        $existing->execute([':s' => $profileId, ':p' => $pageId]);
        $exId = $existing->fetchColumn();
        if ($exId) {
            $pdo->prepare(
                'UPDATE profile_overview_pages SET is_featured = :f, is_active = :a, sort_order = :s, updated_at = :u, updated_by = :by WHERE id = :id'
            )->execute([':f' => $isFeatured, ':a' => $isActive, ':s' => $sort, ':u' => now_utc(), ':by' => $actorId, ':id' => $exId]);
            continue;
        }
        $old = $pdo->prepare(
            'SELECT id FROM profile_overview_pages WHERE profile_overview_id = :s AND page_id = :p AND deleted_at IS NOT NULL ORDER BY id DESC LIMIT 1'
        );
        $old->execute([':s' => $profileId, ':p' => $pageId]);
        $oldId = $old->fetchColumn();
        if ($oldId) {
            restore_row($pdo, 'profile_overview_pages', (int) $oldId, $actorId);
            $pdo->prepare(
                'UPDATE profile_overview_pages SET is_featured = :f, is_active = :a, sort_order = :s, updated_at = :u, updated_by = :by WHERE id = :id'
            )->execute([':f' => $isFeatured, ':a' => $isActive, ':s' => $sort, ':u' => now_utc(), ':by' => $actorId, ':id' => $oldId]);
        } else {
            $pdo->prepare(
                'INSERT INTO profile_overview_pages (profile_overview_id, page_id, is_featured, is_active, sort_order, created_at, created_by)
                 VALUES (:s, :p, :f, :a, :sort, :c, :by)'
            )->execute([
                ':s' => $profileId, ':p' => $pageId, ':f' => $isFeatured, ':a' => $isActive,
                ':sort' => $sort, ':c' => now_utc(), ':by' => $actorId,
            ]);
        }
    }

    $cur = $pdo->prepare('SELECT id, page_id FROM profile_overview_pages WHERE profile_overview_id = :s AND deleted_at IS NULL');
    $cur->execute([':s' => $profileId]);
    foreach ($cur->fetchAll() ?: [] as $row) {
        if (!isset($seen[(int) $row['page_id']])) {
            soft_delete_row($pdo, 'profile_overview_pages', (int) $row['id'], $actorId);
        }
    }
}

/**
 * Split body text into paragraphs for public rendering.
 * Supports plain text (blank-line separated) and rich HTML.
 *
 * @return list<string>
 */
function profile_overview_paragraphs(?string $body): array
{
    $body = trim((string) $body);
    if ($body === '') {
        return [];
    }
    if (preg_match('/<[a-z][\s\S]*>/i', $body)) {
        return [];
    }
    $parts = preg_split("/\R{2,}/", $body) ?: [];
    $out = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '') {
            $out[] = $part;
        }
    }
    return $out;
}

/**
 * Allowlist sanitize for profile overview rich body HTML.
 */
function sanitize_profile_overview_html(?string $html): string
{
    $html = trim((string) $html);
    if ($html === '') {
        return '';
    }
    // Convert plain text (no tags) into paragraph HTML
    if (!preg_match('/<[a-z][\s\S]*>/i', $html)) {
        $paras = profile_overview_paragraphs($html);
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
    // Drop event handlers / javascript URLs
    $html = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html) ?? $html;
    $html = preg_replace('/\shref\s*=\s*("|\')\s*javascript:[^"\']*\1/i', ' href="#"', $html) ?? $html;
    $html = preg_replace('/\s(style|class)\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html) ?? $html;
    return trim($html);
}

function profile_overview_attach_media(array $row): array
{
    $row['image_top_url'] = !empty($row['image_top_path'])
        ? secure_public_media_url((string) $row['image_top_path']) : null;
    $row['image_left_url'] = !empty($row['image_left_path'])
        ? secure_public_media_url((string) $row['image_left_path']) : null;
    $row['image_right_url'] = !empty($row['image_right_path'])
        ? secure_public_media_url((string) $row['image_right_path']) : null;
    $rawBody = (string) ($row['body'] ?? '');
    $row['body_html'] = sanitize_profile_overview_html($rawBody);
    $row['paragraphs'] = profile_overview_paragraphs($rawBody);
    return $row;
}
