-- Contact footer logo image
SET NAMES utf8mb4;
SET time_zone = '+00:00';

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'contact_infos'
      AND COLUMN_NAME = 'logo_path'
);
SET @sql := IF(
    @col = 0,
    'ALTER TABLE contact_infos ADD COLUMN logo_path VARCHAR(500) NULL AFTER business_hours',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
