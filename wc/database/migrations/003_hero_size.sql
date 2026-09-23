-- Legacy: per-hero size_preset (superseded by 004_hero_global_size / site_settings).
-- Kept idempotent so re-running migrate does not re-add the column after 004 drops it.
SET NAMES utf8mb4;
SET time_zone = '+00:00';

SET @settings_exists := (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'site_settings'
);

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'hero'
      AND COLUMN_NAME = 'size_preset'
);

SET @sql := IF(
    @settings_exists = 0 AND @col_exists = 0,
    'ALTER TABLE hero ADD COLUMN size_preset VARCHAR(16) NOT NULL DEFAULT ''md'' AFTER sort_order, ADD KEY idx_hero_size_preset (size_preset)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
