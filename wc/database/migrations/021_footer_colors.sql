-- Footer color defaults in site_settings
SET NAMES utf8mb4;
SET time_zone = '+00:00';

INSERT INTO site_settings (setting_key, setting_value, updated_at)
SELECT s.setting_key, s.setting_value, UTC_TIMESTAMP()
FROM (
    SELECT 'footer_bg_color' AS setting_key, '#111111' AS setting_value UNION ALL
    SELECT 'footer_text_color', '#d8d8d8' UNION ALL
    SELECT 'footer_heading_color', '#ffffff' UNION ALL
    SELECT 'footer_accent_color', '#e0893a' UNION ALL
    SELECT 'footer_social_bg_color', '#2a2a2a'
) AS s
WHERE NOT EXISTS (
    SELECT 1 FROM site_settings x WHERE x.setting_key = s.setting_key
);
