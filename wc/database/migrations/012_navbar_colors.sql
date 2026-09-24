-- Navbar color settings (site_settings keys)
SET NAMES utf8mb4;
SET time_zone = '+00:00';

INSERT INTO site_settings (setting_key, setting_value, updated_at)
VALUES
    ('navbar_transparent', '0', UTC_TIMESTAMP()),
    ('navbar_bg_color', '#12352e', UTC_TIMESTAMP()),
    ('navbar_menu_color', '#ffffff', UTC_TIMESTAMP()),
    ('navbar_active_color', '#e0893a', UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE setting_key = setting_key;
