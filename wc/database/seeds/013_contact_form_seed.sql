-- Contact form permissions, admin pages, menus; enable section on Contact Us
SET NAMES utf8mb4;
SET time_zone = '+00:00';

INSERT INTO permissions (module, action, code, description, is_system, created_at)
SELECT m.module, m.action, CONCAT(m.module, '.', m.action), m.description, 1, UTC_TIMESTAMP()
FROM (
    SELECT 'contact_form' AS module, 'view' AS action, 'View contact form settings' AS description UNION ALL
    SELECT 'contact_form', 'edit', 'Edit contact form settings' UNION ALL
    SELECT 'contact_inquiries', 'view', 'View contact inquiries' UNION ALL
    SELECT 'contact_inquiries', 'edit', 'Edit contact inquiry status' UNION ALL
    SELECT 'contact_inquiries', 'delete', 'Delete contact inquiries'
) AS m
WHERE NOT EXISTS (
    SELECT 1 FROM permissions p WHERE p.code_active = CONCAT(m.module, '.', m.action)
);

INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, UTC_TIMESTAMP()
FROM roles r
CROSS JOIN permissions p
WHERE r.code_active IN ('super_admin', 'admin')
  AND (p.code LIKE 'contact_form.%' OR p.code LIKE 'contact_inquiries.%')
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
  AND p.code IN (
      'contact_form.view', 'contact_form.edit',
      'contact_inquiries.view', 'contact_inquiries.edit'
  )
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.role_id = r.id AND rp.permission_id = p.id AND rp.deleted_at IS NULL
  );

INSERT INTO pages (page_type, title, slug, template_key, is_active, created_at)
SELECT 'admin', 'Contact Form', 'contact-form', 'contact_form', 1, UTC_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM pages p WHERE p.type_slug_active = 'admin:contact-form'
);

INSERT INTO pages (page_type, title, slug, template_key, is_active, created_at)
SELECT 'admin', 'Contact Inquiries', 'contact-inquiries', 'contact_inquiries', 1, UTC_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM pages p WHERE p.type_slug_active = 'admin:contact-inquiries'
);

INSERT INTO menus (menu_type, parent_menu_id, title, icon, url, sort_order, is_active, created_at)
SELECT 'admin', parent.id, 'Contact Form', NULL, '/contact-form', 92, 1, UTC_TIMESTAMP()
FROM menus parent
WHERE parent.menu_type = 'admin'
  AND parent.title = 'Website Content'
  AND parent.parent_menu_id IS NULL
  AND parent.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM menus m
      WHERE m.menu_type = 'admin' AND m.title = 'Contact Form' AND m.url = '/contact-form' AND m.deleted_at IS NULL
  );

INSERT INTO menus (menu_type, parent_menu_id, title, icon, url, sort_order, is_active, created_at)
SELECT 'admin', parent.id, 'Contact Inquiries', NULL, '/contact-inquiries', 93, 1, UTC_TIMESTAMP()
FROM menus parent
WHERE parent.menu_type = 'admin'
  AND parent.title = 'Website Content'
  AND parent.parent_menu_id IS NULL
  AND parent.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM menus m
      WHERE m.menu_type = 'admin' AND m.title = 'Contact Inquiries' AND m.url = '/contact-inquiries' AND m.deleted_at IS NULL
  );

INSERT INTO menu_pages (menu_id, page_id, sort_order, created_at)
SELECT m.id, p.id, 0, UTC_TIMESTAMP()
FROM menus m
JOIN pages p ON p.page_type = 'admin' AND p.slug = 'contact-form' AND p.deleted_at IS NULL
WHERE m.menu_type = 'admin' AND m.title = 'Contact Form' AND m.url = '/contact-form' AND m.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM menu_pages mp WHERE mp.menu_id = m.id AND mp.page_id = p.id AND mp.deleted_at IS NULL
  );

INSERT INTO menu_pages (menu_id, page_id, sort_order, created_at)
SELECT m.id, p.id, 0, UTC_TIMESTAMP()
FROM menus m
JOIN pages p ON p.page_type = 'admin' AND p.slug = 'contact-inquiries' AND p.deleted_at IS NULL
WHERE m.menu_type = 'admin' AND m.title = 'Contact Inquiries' AND m.url = '/contact-inquiries' AND m.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM menu_pages mp WHERE mp.menu_id = m.id AND mp.page_id = p.id AND mp.deleted_at IS NULL
  );

-- Enable contact_form section on Contact Us website page
INSERT INTO page_section_orders (page_id, section_key, sort_order, is_enabled, created_at)
SELECT p.id, 'contact_form', 15, 1, UTC_TIMESTAMP()
FROM pages p
WHERE p.page_type = 'website'
  AND p.deleted_at IS NULL
  AND (p.slug IN ('contact-us', 'Contact-us', 'contact') OR p.template_key = 'contact')
  AND NOT EXISTS (
      SELECT 1 FROM page_section_orders o
      WHERE o.page_id = p.id AND o.section_key = 'contact_form'
  );
