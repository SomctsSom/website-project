-- Allow longer Font Awesome class strings (e.g. "fa-solid fa-user-group")
SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE feature_cards
    MODIFY COLUMN icon_key VARCHAR(100) NOT NULL DEFAULT 'fa fa-star';
