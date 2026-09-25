-- Default section order for Home: Services then Testimonials, then other modules
SET NAMES utf8mb4;
SET time_zone = '+00:00';

INSERT INTO page_section_orders (page_id, section_key, sort_order, is_enabled, created_at)
SELECT p.id, s.section_key, s.sort_order, 1, UTC_TIMESTAMP()
FROM pages p
CROSS JOIN (
    SELECT 'services' AS section_key, 10 AS sort_order UNION ALL
    SELECT 'testimonials', 20 UNION ALL
    SELECT 'profile_overviews', 30 UNION ALL
    SELECT 'vision_missions', 40 UNION ALL
    SELECT 'features', 50
) AS s
WHERE p.page_type = 'website'
  AND p.slug = 'home'
  AND p.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM page_section_orders o
      WHERE o.page_id = p.id AND o.section_key = s.section_key
  );
