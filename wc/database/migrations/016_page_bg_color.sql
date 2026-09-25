-- Per-page body background color (NULL = use global default from site_settings)
SET NAMES utf8mb4;
SET time_zone = '+00:00';

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'pages'
      AND COLUMN_NAME = 'bg_color'
);
SET @sql := IF(
    @col = 0,
    'ALTER TABLE pages ADD COLUMN bg_color VARCHAR(7) NULL DEFAULT NULL AFTER banner_size_preset',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
