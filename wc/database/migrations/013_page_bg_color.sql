-- Global page (body) background color for all public pages
SET NAMES utf8mb4;
SET time_zone = '+00:00';

INSERT INTO site_settings (setting_key, setting_value, updated_at)
VALUES ('page_bg_color', '#f2ebe0', UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE setting_key = setting_key;
