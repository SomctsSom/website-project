-- Header bar transparent background option
SET NAMES utf8mb4;
SET time_zone = '+00:00';

INSERT INTO site_settings (setting_key, setting_value, updated_at)
SELECT 'header_bar_transparent', '0', UTC_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM site_settings x WHERE x.setting_key = 'header_bar_transparent'
);
