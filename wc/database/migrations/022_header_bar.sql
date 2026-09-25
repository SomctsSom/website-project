-- Website header bar defaults in site_settings
SET NAMES utf8mb4;
SET time_zone = '+00:00';

INSERT INTO site_settings (setting_key, setting_value, updated_at)
SELECT s.setting_key, s.setting_value, UTC_TIMESTAMP()
FROM (
    SELECT 'header_bar_enabled' AS setting_key, '1' AS setting_value UNION ALL
    SELECT 'header_bar_transparent', '0' UNION ALL
    SELECT 'header_bar_show_phone', '1' UNION ALL
    SELECT 'header_bar_show_email', '1' UNION ALL
    SELECT 'header_bar_show_hours', '1' UNION ALL
    SELECT 'header_bar_show_social', '1' UNION ALL
    SELECT 'header_bar_bg_color', '#1a1a1a' UNION ALL
    SELECT 'header_bar_text_color', '#ffffff' UNION ALL
    SELECT 'header_bar_accent_color', '#e0893a' UNION ALL
    SELECT 'header_bar_social_color', '#ffffff'
) AS s
WHERE NOT EXISTS (
    SELECT 1 FROM site_settings x WHERE x.setting_key = s.setting_key
);
