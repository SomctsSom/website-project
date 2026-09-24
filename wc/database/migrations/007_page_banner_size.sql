-- Per-page banner size preset (sm|md|lg|xl), same scale as hero
SET NAMES utf8mb4;
SET time_zone = '+00:00';

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'pages'
      AND COLUMN_NAME = 'banner_size_preset'
);
SET @sql := IF(
    @col = 0,
    'ALTER TABLE pages ADD COLUMN banner_size_preset VARCHAR(16) NOT NULL DEFAULT ''md'' AFTER banner_enabled',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
