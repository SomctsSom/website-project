-- Keep only two website-related top menus: Website Management + Website Content.
SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Second website group (sections, colors, contact)
INSERT INTO menus (menu_type, parent_menu_id, title, icon, url, sort_order, is_active, created_at)
SELECT 'admin', NULL, 'Website Content', 'content', NULL, 22, 1, UTC_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM menus m
    WHERE m.menu_type = 'admin' AND m.title = 'Website Content' AND m.parent_menu_id IS NULL AND m.deleted_at IS NULL
);

-- Website Management: structure only
UPDATE menus
SET sort_order = 20,
    updated_at = UTC_TIMESTAMP()
WHERE menu_type = 'admin'
  AND title = 'Website Management'
  AND parent_menu_id IS NULL
  AND deleted_at IS NULL;

UPDATE menus child
JOIN menus parent
  ON parent.menu_type = 'admin'
 AND parent.title = 'Website Management'
 AND parent.parent_menu_id IS NULL
 AND parent.deleted_at IS NULL
SET child.parent_menu_id = parent.id,
    child.sort_order = CASE child.title
        WHEN 'Website Menus' THEN 10
        WHEN 'Website Pages' THEN 20
        ELSE child.sort_order
    END,
    child.updated_at = UTC_TIMESTAMP()
WHERE child.menu_type = 'admin'
  AND child.deleted_at IS NULL
  AND child.title IN ('Website Menus', 'Website Pages');

-- Website Content: all page sections, appearance, contact
UPDATE menus child
JOIN menus parent
  ON parent.menu_type = 'admin'
 AND parent.title = 'Website Content'
 AND parent.parent_menu_id IS NULL
 AND parent.deleted_at IS NULL
SET child.parent_menu_id = parent.id,
    child.sort_order = CASE child.title
        WHEN 'Hero' THEN 10
        WHEN 'Services' THEN 20
        WHEN 'Profile Overviews' THEN 30
        WHEN 'Vision & Mission' THEN 40
        WHEN 'Features' THEN 50
        WHEN 'Testimonials' THEN 60
        WHEN 'Website Header' THEN 65
        WHEN 'Navbar Colors' THEN 70
        WHEN 'Footer Colors' THEN 80
        WHEN 'Contact & Social' THEN 90
        WHEN 'Contact Form' THEN 92
        WHEN 'Contact Inquiries' THEN 93
        ELSE child.sort_order
    END,
    child.updated_at = UTC_TIMESTAMP(),
    child.icon = CASE
        WHEN child.title = 'Contact & Social' THEN NULL
        ELSE child.icon
    END
WHERE child.menu_type = 'admin'
  AND child.deleted_at IS NULL
  AND child.title IN (
      'Hero',
      'Services',
      'Profile Overviews',
      'Vision & Mission',
      'Features',
      'Testimonials',
      'Website Header',
      'Navbar Colors',
      'Footer Colors',
      'Contact & Social',
      'Contact Form',
      'Contact Inquiries'
  );

-- Soft-delete duplicate leaf menus created by earlier parent moves (keep oldest id)
UPDATE menus m
JOIN (
    SELECT title, url, MIN(id) AS keep_id
    FROM menus
    WHERE menu_type = 'admin'
      AND deleted_at IS NULL
      AND url IS NOT NULL
      AND url <> ''
    GROUP BY title, url
    HAVING COUNT(*) > 1
) d ON d.title = m.title AND d.url = m.url
SET m.deleted_at = UTC_TIMESTAMP(),
    m.is_active = 0,
    m.updated_at = UTC_TIMESTAMP()
WHERE m.menu_type = 'admin'
  AND m.deleted_at IS NULL
  AND m.id <> d.keep_id;

-- Soft-delete the old three-way split parents (now empty)
UPDATE menus
SET deleted_at = UTC_TIMESTAMP(),
    is_active = 0,
    updated_at = UTC_TIMESTAMP()
WHERE menu_type = 'admin'
  AND parent_menu_id IS NULL
  AND deleted_at IS NULL
  AND title IN ('Page Sections', 'Appearance');

-- Keep Access Control / System Operations after the website groups
UPDATE menus
SET sort_order = 30,
    updated_at = UTC_TIMESTAMP()
WHERE menu_type = 'admin'
  AND title = 'Access Control'
  AND parent_menu_id IS NULL
  AND deleted_at IS NULL;

UPDATE menus
SET sort_order = 40,
    updated_at = UTC_TIMESTAMP()
WHERE menu_type = 'admin'
  AND title = 'System Operations'
  AND parent_menu_id IS NULL
  AND deleted_at IS NULL;
