-- Contact info: company name
SET NAMES utf8mb4;
SET time_zone = '+00:00';

SET @c1 := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'contact_infos'
      AND COLUMN_NAME = 'company_name'
);
SET @s1 := IF(
    @c1 = 0,
    'ALTER TABLE contact_infos ADD COLUMN company_name VARCHAR(190) NULL AFTER company_summary',
    'SELECT 1'
);
PREPARE st1 FROM @s1; EXECUTE st1; DEALLOCATE PREPARE st1;
