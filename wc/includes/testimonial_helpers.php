<?php
declare(strict_types=1);

function normalize_testimonial_rating(mixed $rating, int $default = 5): int
{
    $n = (int) $rating;
    if ($n < 1 || $n > 5) {
        return $default;
    }
    return $n;
}

/**
 * @param array<int,array<string,mixed>> $assignments
 */
function sync_testimonial_pages(PDO $pdo, int $testimonialId, array $assignments, int $actorId): void
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
            throw new RuntimeException('A page may feature a testimonial only when the assignment is active');
        }

        $existing = $pdo->prepare(
            'SELECT id FROM testimonial_pages WHERE testimonial_id = :s AND page_id = :p AND deleted_at IS NULL LIMIT 1'
        );
        $existing->execute([':s' => $testimonialId, ':p' => $pageId]);
        $exId = $existing->fetchColumn();
        if ($exId) {
            $pdo->prepare(
                'UPDATE testimonial_pages SET is_featured = :f, is_active = :a, sort_order = :s, updated_at = :u, updated_by = :by WHERE id = :id'
            )->execute([':f' => $isFeatured, ':a' => $isActive, ':s' => $sort, ':u' => now_utc(), ':by' => $actorId, ':id' => $exId]);
            continue;
        }
        $old = $pdo->prepare(
            'SELECT id FROM testimonial_pages WHERE testimonial_id = :s AND page_id = :p AND deleted_at IS NOT NULL ORDER BY id DESC LIMIT 1'
        );
        $old->execute([':s' => $testimonialId, ':p' => $pageId]);
        $oldId = $old->fetchColumn();
        if ($oldId) {
            restore_row($pdo, 'testimonial_pages', (int) $oldId, $actorId);
            $pdo->prepare(
                'UPDATE testimonial_pages SET is_featured = :f, is_active = :a, sort_order = :s, updated_at = :u, updated_by = :by WHERE id = :id'
            )->execute([':f' => $isFeatured, ':a' => $isActive, ':s' => $sort, ':u' => now_utc(), ':by' => $actorId, ':id' => $oldId]);
        } else {
            $pdo->prepare(
                'INSERT INTO testimonial_pages (testimonial_id, page_id, is_featured, is_active, sort_order, created_at, created_by)
                 VALUES (:s, :p, :f, :a, :sort, :c, :by)'
            )->execute([
                ':s' => $testimonialId, ':p' => $pageId, ':f' => $isFeatured, ':a' => $isActive,
                ':sort' => $sort, ':c' => now_utc(), ':by' => $actorId,
            ]);
        }
    }

    $cur = $pdo->prepare('SELECT id, page_id FROM testimonial_pages WHERE testimonial_id = :s AND deleted_at IS NULL');
    $cur->execute([':s' => $testimonialId]);
    foreach ($cur->fetchAll() ?: [] as $row) {
        if (!isset($seen[(int) $row['page_id']])) {
            soft_delete_row($pdo, 'testimonial_pages', (int) $row['id'], $actorId);
        }
    }
}

function testimonial_attach_display(array $row): array
{
    $row['rating'] = normalize_testimonial_rating($row['rating'] ?? 5);
    $row['stars_html'] = testimonial_stars_html($row['rating']);
    return $row;
}

function testimonial_stars_html(int $rating): string
{
    $rating = normalize_testimonial_rating($rating);
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $filled = $i <= $rating ? ' is-filled' : '';
        $out .= '<span class="testimonial-star' . $filled . '" aria-hidden="true">★</span>';
    }
    return $out;
}
