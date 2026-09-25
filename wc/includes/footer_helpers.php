<?php
declare(strict_types=1);

/**
 * @return array{bg_color:string,text_color:string,heading_color:string,accent_color:string,social_bg_color:string}
 */
function footer_color_defaults(): array
{
    return [
        'bg_color' => '#111111',
        'text_color' => '#d8d8d8',
        'heading_color' => '#ffffff',
        'accent_color' => '#e0893a',
        'social_bg_color' => '#2a2a2a',
    ];
}

/**
 * @return array{bg_color:string,text_color:string,heading_color:string,accent_color:string,social_bg_color:string}
 */
function get_footer_colors(?PDO $pdo = null): array
{
    $defaults = footer_color_defaults();
    $pdo = $pdo ?? db();
    try {
        $stmt = $pdo->query(
            "SELECT setting_key, setting_value FROM site_settings
             WHERE setting_key IN (
                'footer_bg_color','footer_text_color','footer_heading_color',
                'footer_accent_color','footer_social_bg_color'
             )"
        );
        $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: []) : [];
        if (isset($rows['footer_bg_color'])) {
            $defaults['bg_color'] = sanitize_navbar_hex($rows['footer_bg_color'], $defaults['bg_color']);
        }
        if (isset($rows['footer_text_color'])) {
            $defaults['text_color'] = sanitize_navbar_hex($rows['footer_text_color'], $defaults['text_color']);
        }
        if (isset($rows['footer_heading_color'])) {
            $defaults['heading_color'] = sanitize_navbar_hex($rows['footer_heading_color'], $defaults['heading_color']);
        }
        if (isset($rows['footer_accent_color'])) {
            $defaults['accent_color'] = sanitize_navbar_hex($rows['footer_accent_color'], $defaults['accent_color']);
        }
        if (isset($rows['footer_social_bg_color'])) {
            $defaults['social_bg_color'] = sanitize_navbar_hex($rows['footer_social_bg_color'], $defaults['social_bg_color']);
        }
    } catch (Throwable $e) {
        // Table may not exist yet
    }
    return $defaults;
}

/**
 * @param array<string,mixed> $input
 * @return array{bg_color:string,text_color:string,heading_color:string,accent_color:string,social_bg_color:string}
 */
function set_footer_colors(PDO $pdo, array $input, ?int $actorId = null): array
{
    $defaults = footer_color_defaults();
    $current = get_footer_colors($pdo);
    $bg = sanitize_navbar_hex($input['bg_color'] ?? $current['bg_color'], $defaults['bg_color']);
    $text = sanitize_navbar_hex($input['text_color'] ?? $current['text_color'], $defaults['text_color']);
    $heading = sanitize_navbar_hex($input['heading_color'] ?? $current['heading_color'], $defaults['heading_color']);
    $accent = sanitize_navbar_hex($input['accent_color'] ?? $current['accent_color'], $defaults['accent_color']);
    $socialBg = sanitize_navbar_hex($input['social_bg_color'] ?? $current['social_bg_color'], $defaults['social_bg_color']);

    $map = [
        'footer_bg_color' => $bg,
        'footer_text_color' => $text,
        'footer_heading_color' => $heading,
        'footer_accent_color' => $accent,
        'footer_social_bg_color' => $socialBg,
    ];
    $stmt = $pdo->prepare(
        "INSERT INTO site_settings (setting_key, setting_value, updated_at, updated_by)
         VALUES (:k, :v, :u, :by)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value),
           updated_at = VALUES(updated_at), updated_by = VALUES(updated_by)"
    );
    foreach ($map as $key => $value) {
        $stmt->execute([
            ':k' => $key,
            ':v' => $value,
            ':u' => now_utc(),
            ':by' => $actorId,
        ]);
    }

    return [
        'bg_color' => $bg,
        'text_color' => $text,
        'heading_color' => $heading,
        'accent_color' => $accent,
        'social_bg_color' => $socialBg,
    ];
}
