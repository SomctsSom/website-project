-- Contact & Social permissions, admin menu, and Home seed data
SET NAMES utf8mb4;
SET time_zone = '+00:00';

INSERT INTO permissions (module, action, code, description, is_system, created_at)
SELECT m.module, m.action, CONCAT(m.module, '.', m.action), m.description, 1, UTC_TIMESTAMP()
FROM (
    SELECT 'contact_social' AS module, 'view' AS action, 'View Contact & Social' AS description UNION ALL
    SELECT 'contact_social', 'create', 'Create Contact & Social' UNION ALL
    SELECT 'contact_social', 'edit', 'Edit Contact & Social' UNION ALL
    SELECT 'contact_social', 'delete', 'Soft-delete Contact & Social' UNION ALL
    SELECT 'contact_social', 'restore', 'Restore Contact & Social'
) AS m
WHERE NOT EXISTS (
    SELECT 1 FROM permissions p WHERE p.code_active = CONCAT(m.module, '.', m.action)
);

INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, UTC_TIMESTAMP()
FROM roles r
CROSS JOIN permissions p
WHERE r.code_active IN ('super_admin', 'admin')
  AND p.code LIKE 'contact_social.%'
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
  AND p.code IN ('contact_social.view', 'contact_social.create', 'contact_social.edit')
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.role_id = r.id AND rp.permission_id = p.id AND rp.deleted_at IS NULL
  );

INSERT INTO pages (page_type, title, slug, template_key, is_active, created_at)
SELECT 'admin', 'Contact & Social', 'contact-social', 'contact_social', 1, UTC_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM pages p WHERE p.type_slug_active = 'admin:contact-social'
);

INSERT INTO menus (menu_type, parent_menu_id, title, icon, url, sort_order, is_active, created_at)
SELECT 'admin', parent.id, 'Contact & Social', NULL, '/contact-social', 90, 1, UTC_TIMESTAMP()
FROM menus parent
WHERE parent.menu_type = 'admin'
  AND parent.title = 'Website Content'
  AND parent.parent_menu_id IS NULL
  AND parent.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM menus m
      WHERE m.menu_type = 'admin' AND m.title = 'Contact & Social' AND m.url = '/contact-social' AND m.deleted_at IS NULL
  );

INSERT INTO menu_pages (menu_id, page_id, sort_order, created_at)
SELECT m.id, p.id, 0, UTC_TIMESTAMP()
FROM menus m
JOIN pages p ON p.page_type = 'admin' AND p.slug = 'contact-social' AND p.deleted_at IS NULL
WHERE m.menu_type = 'admin' AND m.title = 'Contact & Social' AND m.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM menu_pages mp WHERE mp.menu_id = m.id AND mp.page_id = p.id AND mp.deleted_at IS NULL
  );

-- Social links
INSERT INTO social_links (icon_class, link_url, label, sort_order, is_visible, is_active, created_at)
SELECT s.icon_class, s.link_url, s.label, s.sort_order, 1, 1, UTC_TIMESTAMP()
FROM (
    SELECT 'fab fa-facebook-f' AS icon_class, 'https://facebook.com' AS link_url, 'Facebook' AS label, 10 AS sort_order UNION ALL
    SELECT 'fab fa-twitter', 'https://twitter.com', 'Twitter', 20 UNION ALL
    SELECT 'fab fa-linkedin-in', 'https://linkedin.com', 'LinkedIn', 30 UNION ALL
    SELECT 'fab fa-instagram', 'https://instagram.com', 'Instagram', 40
) AS s
WHERE NOT EXISTS (
    SELECT 1 FROM social_links sl
    WHERE sl.label = s.label AND sl.deleted_at IS NULL
);

INSERT INTO social_link_pages (social_link_id, page_id, is_featured, is_active, sort_order, created_at)
SELECT sl.id, p.id, 0, 1, sl.sort_order, UTC_TIMESTAMP()
FROM social_links sl
CROSS JOIN pages p
WHERE p.page_type = 'website' AND p.slug = 'home' AND p.deleted_at IS NULL
  AND sl.deleted_at IS NULL
  AND sl.label IN ('Facebook', 'Twitter', 'LinkedIn', 'Instagram')
  AND NOT EXISTS (
      SELECT 1 FROM social_link_pages x
      WHERE x.social_link_id = sl.id AND x.page_id = p.id AND x.deleted_at IS NULL
  );

-- Contact block
INSERT INTO contact_infos (
    company_summary, company_name, tagline, email, business_hours, sort_order, is_active, created_at
)
SELECT
    'Professional dispatching support for Trucking and NEMT transportation businesses.',
    'Navo Dispatch',
    'Professional Truck & NEMT Dispatching Services',
    'navo@gmail.com',
    'Monday - Friday (08:00AM - 06:00PM)',
    10,
    1,
    UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM contact_infos WHERE deleted_at IS NULL LIMIT 1);

UPDATE contact_infos
SET
    company_summary = 'Professional dispatching support for Trucking and NEMT transportation businesses.',
    company_name = COALESCE(NULLIF(company_name, ''), 'Navo Dispatch'),
    tagline = 'Professional Truck & NEMT Dispatching Services',
    email = 'navo@gmail.com',
    business_hours = 'Monday - Friday (08:00AM - 06:00PM)',
    is_active = 1,
    updated_at = UTC_TIMESTAMP()
WHERE deleted_at IS NULL
ORDER BY id ASC
LIMIT 1;

INSERT INTO contact_addresses (contact_info_id, label, address_text, sort_order, created_at)
SELECT c.id, 'Office', 'Isgooska Taleex, Dagmada Hodan', 10, UTC_TIMESTAMP()
FROM contact_infos c
WHERE c.deleted_at IS NULL
  AND NOT EXISTS (SELECT 1 FROM contact_addresses a WHERE a.contact_info_id = c.id)
ORDER BY c.id ASC
LIMIT 1;

UPDATE contact_addresses
SET label = 'Office',
    address_text = 'Isgooska Taleex, Dagmada Hodan'
WHERE contact_info_id = (
    SELECT id FROM (SELECT id FROM contact_infos WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1) t
)
ORDER BY sort_order ASC, id ASC
LIMIT 1;

INSERT INTO contact_phones (contact_info_id, label, phone, sort_order, created_at)
SELECT c.id, 'Main', '+252618941010', 10, UTC_TIMESTAMP()
FROM contact_infos c
WHERE c.deleted_at IS NULL
  AND NOT EXISTS (SELECT 1 FROM contact_phones p WHERE p.contact_info_id = c.id)
ORDER BY c.id ASC
LIMIT 1;

UPDATE contact_phones
SET label = 'Main',
    phone = '+252618941010'
WHERE contact_info_id = (
    SELECT id FROM (SELECT id FROM contact_infos WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1) t
)
ORDER BY sort_order ASC, id ASC
LIMIT 1;

INSERT INTO contact_info_pages (contact_info_id, page_id, is_featured, is_active, sort_order, created_at)
SELECT c.id, p.id, 1, 1, 0, UTC_TIMESTAMP()
FROM contact_infos c
CROSS JOIN pages p
WHERE p.page_type = 'website' AND p.slug = 'home' AND p.deleted_at IS NULL
  AND c.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM contact_info_pages x
      WHERE x.contact_info_id = c.id AND x.page_id = p.id AND x.deleted_at IS NULL
  );

INSERT INTO page_section_orders (page_id, section_key, sort_order, is_enabled, created_at)
SELECT p.id, 'contact_social', 60, 1, UTC_TIMESTAMP()
FROM pages p
WHERE p.page_type = 'website' AND p.slug = 'home' AND p.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM page_section_orders o
      WHERE o.page_id = p.id AND o.section_key = 'contact_social'
  );
