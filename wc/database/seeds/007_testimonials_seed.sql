-- Idempotent seeds for Testimonials module
SET NAMES utf8mb4;
SET time_zone = '+00:00';

INSERT INTO permissions (module, action, code, description, is_system, created_at)
SELECT m.module, m.action, CONCAT(m.module, '.', m.action), m.description, 1, UTC_TIMESTAMP()
FROM (
    SELECT 'testimonials' AS module, 'view' AS action, 'View testimonials' AS description UNION ALL
    SELECT 'testimonials', 'create', 'Create testimonials' UNION ALL
    SELECT 'testimonials', 'edit', 'Edit testimonials' UNION ALL
    SELECT 'testimonials', 'delete', 'Soft-delete testimonials' UNION ALL
    SELECT 'testimonials', 'restore', 'Restore testimonials'
) AS m
WHERE NOT EXISTS (
    SELECT 1 FROM permissions p WHERE p.code_active = CONCAT(m.module, '.', m.action)
);

INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, UTC_TIMESTAMP()
FROM roles r
CROSS JOIN permissions p
WHERE r.code_active = 'super_admin'
  AND p.code LIKE 'testimonials.%'
  AND p.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.role_id = r.id AND rp.permission_id = p.id AND rp.deleted_at IS NULL
  );

INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, UTC_TIMESTAMP()
FROM roles r
CROSS JOIN permissions p
WHERE r.code_active = 'admin'
  AND p.code LIKE 'testimonials.%'
  AND p.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.role_id = r.id AND rp.permission_id = p.id AND rp.deleted_at IS NULL
  );

INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, UTC_TIMESTAMP()
FROM roles r
CROSS JOIN permissions p
WHERE r.code_active = 'editor'
  AND p.deleted_at IS NULL
  AND p.code IN ('testimonials.view', 'testimonials.create', 'testimonials.edit')
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.role_id = r.id AND rp.permission_id = p.id AND rp.deleted_at IS NULL
  );

INSERT INTO pages (page_type, title, slug, template_key, is_active, created_at)
SELECT 'admin', 'Testimonials', 'testimonials', 'testimonials', 1, UTC_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM pages p WHERE p.type_slug_active = 'admin:testimonials'
);

INSERT INTO menus (menu_type, parent_menu_id, title, icon, url, sort_order, is_active, created_at)
SELECT 'admin', parent.id, 'Testimonials', NULL, '/testimonials', 60, 1, UTC_TIMESTAMP()
FROM menus parent
WHERE parent.menu_type = 'admin'
  AND parent.title = 'Website Content'
  AND parent.parent_menu_id IS NULL
  AND parent.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM menus m
      WHERE m.menu_type = 'admin' AND m.title = 'Testimonials' AND m.deleted_at IS NULL
  );

INSERT INTO menu_pages (menu_id, page_id, sort_order, created_at)
SELECT m.id, p.id, 0, UTC_TIMESTAMP()
FROM menus m
JOIN pages p ON p.page_type = 'admin' AND p.slug = 'testimonials' AND p.deleted_at IS NULL
WHERE m.menu_type = 'admin' AND m.title = 'Testimonials' AND m.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM menu_pages mp WHERE mp.menu_id = m.id AND mp.page_id = p.id AND mp.deleted_at IS NULL
  );

-- Sample testimonials (5 customers) — idempotent by author_name
INSERT INTO testimonials (rating, quote, author_name, author_role, sort_order, is_active, created_at)
SELECT s.rating, s.quote, s.author_name, s.author_role, s.sort_order, 1, UTC_TIMESTAMP()
FROM (
    SELECT 5 AS rating,
           'Navo Dispatch helped keep my truck booked with clear communication and solid load support.' AS quote,
           'Michael R.' AS author_name,
           'Driver · Owner-Operator' AS author_role,
           10 AS sort_order
    UNION ALL
    SELECT 5,
           'Reliable dispatch every week. They find quality loads and keep me updated from pickup to delivery.',
           'Sarah K.',
           'Fleet Owner',
           20
    UNION ALL
    SELECT 5,
           'Professional team that understands the road. My revenue improved within the first month.',
           'James T.',
           'Owner-Operator',
           30
    UNION ALL
    SELECT 4,
           'Great support and honest rates. I always know what to expect before I take a load.',
           'Amina H.',
           'Long-Haul Driver',
           40
    UNION ALL
    SELECT 5,
           'They treat drivers with respect and handle paperwork fast. Highly recommended dispatch partner.',
           'David L.',
           'Driver · Small Fleet',
           50
) AS s
WHERE NOT EXISTS (
    SELECT 1 FROM testimonials t
    WHERE t.author_name = s.author_name AND t.deleted_at IS NULL
);

-- Assign sample testimonials to Home (and About when present)
INSERT INTO testimonial_pages (testimonial_id, page_id, is_featured, is_active, sort_order, created_at)
SELECT t.id, p.id, IF(t.sort_order = 10, 1, 0), 1, t.sort_order, UTC_TIMESTAMP()
FROM testimonials t
CROSS JOIN pages p
WHERE p.page_type = 'website'
  AND p.slug IN ('home', 'about')
  AND p.deleted_at IS NULL
  AND t.deleted_at IS NULL
  AND t.author_name IN ('Michael R.', 'Sarah K.', 'James T.', 'Amina H.', 'David L.')
  AND NOT EXISTS (
      SELECT 1 FROM testimonial_pages tp
      WHERE tp.testimonial_id = t.id AND tp.page_id = p.id AND tp.deleted_at IS NULL
  );
