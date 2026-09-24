-- Page banner structure (website pages)
-- eyebrow + title + subtitle + background image (not flat color)
SET NAMES utf8mb4;
SET time_zone = '+00:00';

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pages' AND COLUMN_NAME = 'banner_eyebrow'
);
SET @sql := IF(@col = 0,
    'ALTER TABLE pages
        ADD COLUMN banner_eyebrow VARCHAR(150) NULL AFTER template_key,
        ADD COLUMN banner_title VARCHAR(200) NULL AFTER banner_eyebrow,
        ADD COLUMN banner_subtitle VARCHAR(255) NULL AFTER banner_title,
        ADD COLUMN banner_image_path VARCHAR(500) NULL AFTER banner_subtitle',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Sample copy for existing website pages (image uploaded later in Admin)
UPDATE pages
SET banner_eyebrow = 'PROFESSIONAL DISPATCHING',
    banner_title = 'About Navo Dispatch LLC',
    banner_subtitle = 'Professional Truck & NEMT Dispatching Services'
WHERE page_type = 'website' AND slug = 'about' AND deleted_at IS NULL
  AND (banner_title IS NULL OR banner_title = '');

UPDATE pages
SET banner_eyebrow = 'OUR SERVICES',
    banner_title = 'Services',
    banner_subtitle = 'Dispatch solutions tailored to your fleet'
WHERE page_type = 'website' AND slug = 'services' AND deleted_at IS NULL
  AND (banner_title IS NULL OR banner_title = '');

UPDATE pages
SET banner_eyebrow = 'WELCOME',
    banner_title = 'Home',
    banner_subtitle = 'Your public website experience'
WHERE page_type = 'website' AND slug = 'home' AND deleted_at IS NULL
  AND (banner_title IS NULL OR banner_title = '');
