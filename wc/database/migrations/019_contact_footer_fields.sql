-- Contact info: tagline, email, business hours for footer
SET NAMES utf8mb4;
SET time_zone = '+00:00';

SET @c1 := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contact_infos' AND COLUMN_NAME = 'tagline');
SET @s1 := IF(@c1 = 0, 'ALTER TABLE contact_infos ADD COLUMN tagline VARCHAR(255) NULL AFTER company_summary', 'SELECT 1');
PREPARE st1 FROM @s1; EXECUTE st1; DEALLOCATE PREPARE st1;

SET @c2 := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contact_infos' AND COLUMN_NAME = 'email');
SET @s2 := IF(@c2 = 0, 'ALTER TABLE contact_infos ADD COLUMN email VARCHAR(190) NULL AFTER tagline', 'SELECT 1');
PREPARE st2 FROM @s2; EXECUTE st2; DEALLOCATE PREPARE st2;

SET @c3 := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contact_infos' AND COLUMN_NAME = 'business_hours');
SET @s3 := IF(@c3 = 0, 'ALTER TABLE contact_infos ADD COLUMN business_hours VARCHAR(255) NULL AFTER email', 'SELECT 1');
PREPARE st3 FROM @s3; EXECUTE st3; DEALLOCATE PREPARE st3;

UPDATE contact_infos
SET tagline = COALESCE(tagline, 'Professional Truck & NEMT Dispatching Services'),
    email = COALESCE(email, 'navo@gmail.com'),
    business_hours = COALESCE(business_hours, 'Monday - Friday (08:00AM - 06:00PM)')
WHERE deleted_at IS NULL;
