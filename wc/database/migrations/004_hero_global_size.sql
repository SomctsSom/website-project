-- One global hero section size for all heroes (stored in site_settings)
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(64) NOT NULL,
    setting_value VARCHAR(255) NOT NULL,
    updated_at DATETIME NULL,
    updated_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed global size from any existing per-hero value (prefer lg/xl if present), else md
SET @seed_size := (
    SELECT size_preset FROM hero
    WHERE deleted_at IS NULL
    ORDER BY FIELD(size_preset, 'xl', 'lg', 'md', 'sm') ASC, id ASC
    LIMIT 1
);
SET @seed_size := IFNULL(@seed_size, 'md');

INSERT INTO site_settings (setting_key, setting_value, updated_at)
VALUES ('hero_size_preset', @seed_size, UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- Drop per-hero size column (global setting replaces it)
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'hero'
      AND COLUMN_NAME = 'size_preset'
);

SET @sql := IF(
    @col_exists > 0,
    'ALTER TABLE hero DROP COLUMN size_preset',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
