-- Per-page card background color (NULL = default white/panel)
SET NAMES utf8mb4;
SET time_zone = '+00:00';

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'pages'
      AND COLUMN_NAME = 'card_bg_color'
);
SET @sql := IF(
    @col = 0,
    'ALTER TABLE pages ADD COLUMN card_bg_color VARCHAR(7) NULL DEFAULT NULL AFTER bg_color',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
