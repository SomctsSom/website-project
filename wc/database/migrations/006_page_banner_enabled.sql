-- Toggle: show page banner on public site only when enabled
SET NAMES utf8mb4;
SET time_zone = '+00:00';

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'pages'
      AND COLUMN_NAME = 'banner_enabled'
);
SET @sql := IF(
    @col = 0,
    'ALTER TABLE pages ADD COLUMN banner_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER banner_image_path',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
