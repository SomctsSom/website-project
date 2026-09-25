-- Idempotent seed data for Phase 0 + Phase 1
-- Safe to re-run: uses INSERT ... SELECT ... WHERE NOT EXISTS patterns.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Roles
INSERT INTO roles (name, code, description, is_system, created_at)
SELECT 'Super Admin', 'super_admin', 'Full system access; cannot remove last active Super Admin', 1, UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE code_active = 'super_admin');

INSERT INTO roles (name, code, description, is_system, created_at)
SELECT 'Admin', 'admin', 'Administrative access excluding critical system locks', 1, UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE code_active = 'admin');

INSERT INTO roles (name, code, description, is_system, created_at)
SELECT 'Editor', 'editor', 'Content editing for website menus, pages, and hero', 1, UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE code_active = 'editor');

-- Permissions (module.action)
INSERT INTO permissions (module, action, code, description, is_system, created_at)
SELECT m.module, m.action, CONCAT(m.module, '.', m.action), m.description, 1, UTC_TIMESTAMP()
FROM (
    SELECT 'users' AS module, 'view' AS action, 'View users' AS description UNION ALL
    SELECT 'users', 'create', 'Create users' UNION ALL
    SELECT 'users', 'edit', 'Edit users' UNION ALL
    SELECT 'users', 'delete', 'Soft-delete users' UNION ALL
    SELECT 'users', 'restore', 'Restore users' UNION ALL
    SELECT 'roles', 'view', 'View roles' UNION ALL
    SELECT 'roles', 'create', 'Create roles' UNION ALL
    SELECT 'roles', 'edit', 'Edit roles' UNION ALL
    SELECT 'roles', 'delete', 'Soft-delete roles' UNION ALL
    SELECT 'roles', 'restore', 'Restore roles' UNION ALL
    SELECT 'permissions', 'view', 'View permissions' UNION ALL
    SELECT 'permissions', 'create', 'Create permissions' UNION ALL
    SELECT 'permissions', 'edit', 'Edit permissions' UNION ALL
    SELECT 'permissions', 'delete', 'Soft-delete permissions' UNION ALL
    SELECT 'permissions', 'restore', 'Restore permissions' UNION ALL
    SELECT 'menus', 'view', 'View menus' UNION ALL
    SELECT 'menus', 'create', 'Create menus' UNION ALL
    SELECT 'menus', 'edit', 'Edit menus' UNION ALL
    SELECT 'menus', 'delete', 'Soft-delete menus' UNION ALL
    SELECT 'menus', 'restore', 'Restore menus' UNION ALL
    SELECT 'pages', 'view', 'View pages' UNION ALL
    SELECT 'pages', 'create', 'Create pages' UNION ALL
    SELECT 'pages', 'edit', 'Edit pages' UNION ALL
    SELECT 'pages', 'delete', 'Soft-delete pages' UNION ALL
    SELECT 'pages', 'restore', 'Restore pages' UNION ALL
    SELECT 'hero', 'view', 'View hero' UNION ALL
    SELECT 'hero', 'create', 'Create hero' UNION ALL
    SELECT 'hero', 'edit', 'Edit hero' UNION ALL
    SELECT 'hero', 'delete', 'Soft-delete hero' UNION ALL
    SELECT 'hero', 'restore', 'Restore hero' UNION ALL
    SELECT 'trash', 'view', 'View trash' UNION ALL
    SELECT 'trash', 'restore', 'Restore from trash' UNION ALL
    SELECT 'audit', 'view', 'View audit logs' UNION ALL
    SELECT 'dashboard', 'view', 'View dashboard'
) AS m
WHERE NOT EXISTS (
    SELECT 1 FROM permissions p WHERE p.code_active = CONCAT(m.module, '.', m.action)
);

-- Super Admin: all permissions
INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, UTC_TIMESTAMP()
FROM roles r
CROSS JOIN permissions p
WHERE r.code_active = 'super_admin'
  AND p.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.role_id = r.id AND rp.permission_id = p.id AND rp.deleted_at IS NULL
  );

-- Admin: most permissions except permission delete
INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, UTC_TIMESTAMP()
FROM roles r
CROSS JOIN permissions p
WHERE r.code_active = 'admin'
  AND p.deleted_at IS NULL
  AND p.code NOT IN ('permissions.delete', 'permissions.create')
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.role_id = r.id AND rp.permission_id = p.id AND rp.deleted_at IS NULL
  );

-- Editor: content modules
INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, UTC_TIMESTAMP()
FROM roles r
CROSS JOIN permissions p
WHERE r.code_active = 'editor'
  AND p.deleted_at IS NULL
  AND p.code IN (
      'dashboard.view',
      'menus.view', 'menus.create', 'menus.edit',
      'pages.view', 'pages.create', 'pages.edit',
      'hero.view', 'hero.create', 'hero.edit'
  )
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.role_id = r.id AND rp.permission_id = p.id AND rp.deleted_at IS NULL
  );

-- Admin pages (metadata only — template_key must match approved keys)
INSERT INTO pages (page_type, title, slug, template_key, is_active, created_at)
SELECT 'admin', t.title, t.slug, t.template_key, 1, UTC_TIMESTAMP()
FROM (
    SELECT 'Dashboard' AS title, 'dashboard' AS slug, 'dashboard' AS template_key UNION ALL
    SELECT 'Users', 'users', 'users' UNION ALL
    SELECT 'Roles', 'roles', 'roles' UNION ALL
    SELECT 'Permissions', 'permissions', 'permissions' UNION ALL
    SELECT 'Website Menus', 'website-menus', 'website_menus' UNION ALL
    SELECT 'Admin Menus', 'admin-menus', 'admin_menus' UNION ALL
    SELECT 'Website Pages', 'website-pages', 'website_pages' UNION ALL
    SELECT 'Admin Pages', 'admin-pages', 'admin_pages' UNION ALL
    SELECT 'Hero', 'hero', 'hero' UNION ALL
    SELECT 'Trash', 'trash', 'trash' UNION ALL
    SELECT 'Audit Logs', 'audit-logs', 'audit_logs' UNION ALL
    SELECT 'Account', 'account', 'account'
) AS t
WHERE NOT EXISTS (
    SELECT 1 FROM pages p WHERE p.type_slug_active = CONCAT('admin:', t.slug)
);

-- Website Home page
INSERT INTO pages (page_type, title, slug, template_key, is_active, created_at)
SELECT 'website', 'Home', 'home', 'home', 1, UTC_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM pages p WHERE p.type_slug_active = 'website:home'
);

INSERT INTO pages (page_type, title, slug, template_key, is_active, created_at)
SELECT 'website', 'About', 'about', 'about', 1, UTC_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM pages p WHERE p.type_slug_active = 'website:about'
);

-- Website menus
INSERT INTO menus (menu_type, parent_menu_id, title, icon, url, sort_order, is_active, created_at)
SELECT 'website', NULL, 'Home', NULL, '/home', 10, 1, UTC_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM menus m WHERE m.menu_type = 'website' AND m.title = 'Home' AND m.parent_menu_id IS NULL AND m.deleted_at IS NULL
);

INSERT INTO menus (menu_type, parent_menu_id, title, icon, url, sort_order, is_active, created_at)
SELECT 'website', NULL, 'About', NULL, '/about', 20, 1, UTC_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM menus m WHERE m.menu_type = 'website' AND m.title = 'About' AND m.parent_menu_id IS NULL AND m.deleted_at IS NULL
);

-- Link website menus to pages
INSERT INTO menu_pages (menu_id, page_id, sort_order, created_at)
SELECT m.id, p.id, 0, UTC_TIMESTAMP()
FROM menus m
JOIN pages p ON p.page_type = 'website' AND p.slug = 'home' AND p.deleted_at IS NULL
WHERE m.menu_type = 'website' AND m.title = 'Home' AND m.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM menu_pages mp WHERE mp.menu_id = m.id AND mp.page_id = p.id AND mp.deleted_at IS NULL
  );

INSERT INTO menu_pages (menu_id, page_id, sort_order, created_at)
SELECT m.id, p.id, 0, UTC_TIMESTAMP()
FROM menus m
JOIN pages p ON p.page_type = 'website' AND p.slug = 'about' AND p.deleted_at IS NULL
WHERE m.menu_type = 'website' AND m.title = 'About' AND m.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM menu_pages mp WHERE mp.menu_id = m.id AND mp.page_id = p.id AND mp.deleted_at IS NULL
  );

-- Admin top-level menu groups
INSERT INTO menus (menu_type, parent_menu_id, title, icon, url, sort_order, is_active, created_at)
SELECT 'admin', NULL, 'Dashboard', 'dashboard', '/dashboard', 10, 1, UTC_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM menus m WHERE m.menu_type = 'admin' AND m.title = 'Dashboard' AND m.parent_menu_id IS NULL AND m.deleted_at IS NULL
);

INSERT INTO menus (menu_type, parent_menu_id, title, icon, url, sort_order, is_active, created_at)
SELECT 'admin', NULL, 'Website Management', 'website', NULL, 20, 1, UTC_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM menus m WHERE m.menu_type = 'admin' AND m.title = 'Website Management' AND m.parent_menu_id IS NULL AND m.deleted_at IS NULL
);

INSERT INTO menus (menu_type, parent_menu_id, title, icon, url, sort_order, is_active, created_at)
SELECT 'admin', NULL, 'Access Control', 'access', NULL, 30, 1, UTC_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM menus m WHERE m.menu_type = 'admin' AND m.title = 'Access Control' AND m.parent_menu_id IS NULL AND m.deleted_at IS NULL
);

INSERT INTO menus (menu_type, parent_menu_id, title, icon, url, sort_order, is_active, created_at)
SELECT 'admin', NULL, 'System Operations', 'system', NULL, 40, 1, UTC_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM menus m WHERE m.menu_type = 'admin' AND m.title = 'System Operations' AND m.parent_menu_id IS NULL AND m.deleted_at IS NULL
);

-- Admin submenus under Website Management (structure only)
INSERT INTO menus (menu_type, parent_menu_id, title, icon, url, sort_order, is_active, created_at)
SELECT 'admin', parent.id, child.title, NULL, child.url, child.sort_order, 1, UTC_TIMESTAMP()
FROM menus parent
JOIN (
    SELECT 'Website Menus' AS title, '/website-menus' AS url, 10 AS sort_order UNION ALL
    SELECT 'Website Pages', '/website-pages', 20
) AS child
WHERE parent.menu_type = 'admin' AND parent.title = 'Website Management' AND parent.parent_menu_id IS NULL AND parent.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM menus m
      WHERE m.menu_type = 'admin' AND m.parent_menu_id = parent.id AND m.title = child.title AND m.deleted_at IS NULL
  );

-- Website Content group (sections, colors, contact)
INSERT INTO menus (menu_type, parent_menu_id, title, icon, url, sort_order, is_active, created_at)
SELECT 'admin', NULL, 'Website Content', 'content', NULL, 22, 1, UTC_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM menus m WHERE m.menu_type = 'admin' AND m.title = 'Website Content' AND m.parent_menu_id IS NULL AND m.deleted_at IS NULL
);

INSERT INTO menus (menu_type, parent_menu_id, title, icon, url, sort_order, is_active, created_at)
SELECT 'admin', parent.id, 'Hero', NULL, '/hero', 10, 1, UTC_TIMESTAMP()
FROM menus parent
WHERE parent.menu_type = 'admin' AND parent.title = 'Website Content' AND parent.parent_menu_id IS NULL AND parent.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM menus m
      WHERE m.menu_type = 'admin' AND m.title = 'Hero' AND m.url = '/hero' AND m.deleted_at IS NULL
  );

-- Admin submenus under Access Control
INSERT INTO menus (menu_type, parent_menu_id, title, icon, url, sort_order, is_active, created_at)
SELECT 'admin', parent.id, child.title, NULL, child.url, child.sort_order, 1, UTC_TIMESTAMP()
FROM menus parent
JOIN (
    SELECT 'Users' AS title, '/users' AS url, 10 AS sort_order UNION ALL
    SELECT 'Roles', '/roles', 20 UNION ALL
    SELECT 'Permissions', '/permissions', 30
) AS child
WHERE parent.menu_type = 'admin' AND parent.title = 'Access Control' AND parent.parent_menu_id IS NULL AND parent.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM menus m
      WHERE m.menu_type = 'admin' AND m.parent_menu_id = parent.id AND m.title = child.title AND m.deleted_at IS NULL
  );

-- Admin submenus under System Operations
INSERT INTO menus (menu_type, parent_menu_id, title, icon, url, sort_order, is_active, created_at)
SELECT 'admin', parent.id, child.title, NULL, child.url, child.sort_order, 1, UTC_TIMESTAMP()
FROM menus parent
JOIN (
    SELECT 'Admin Menus' AS title, '/admin-menus' AS url, 10 AS sort_order UNION ALL
    SELECT 'Admin Pages', '/admin-pages', 20 UNION ALL
    SELECT 'Trash', '/trash', 30 UNION ALL
    SELECT 'Audit Logs', '/audit-logs', 40
) AS child
WHERE parent.menu_type = 'admin' AND parent.title = 'System Operations' AND parent.parent_menu_id IS NULL AND parent.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM menus m
      WHERE m.menu_type = 'admin' AND m.parent_menu_id = parent.id AND m.title = child.title AND m.deleted_at IS NULL
  );

-- Link admin leaf menus to admin pages
INSERT INTO menu_pages (menu_id, page_id, sort_order, created_at)
SELECT m.id, p.id, 0, UTC_TIMESTAMP()
FROM menus m
JOIN pages p ON p.page_type = 'admin' AND p.deleted_at IS NULL
  AND (
      (m.title = 'Dashboard' AND p.slug = 'dashboard') OR
      (m.title = 'Users' AND p.slug = 'users') OR
      (m.title = 'Roles' AND p.slug = 'roles') OR
      (m.title = 'Permissions' AND p.slug = 'permissions') OR
      (m.title = 'Website Menus' AND p.slug = 'website-menus') OR
      (m.title = 'Admin Menus' AND p.slug = 'admin-menus') OR
      (m.title = 'Website Pages' AND p.slug = 'website-pages') OR
      (m.title = 'Admin Pages' AND p.slug = 'admin-pages') OR
      (m.title = 'Hero' AND p.slug = 'hero') OR
      (m.title = 'Trash' AND p.slug = 'trash') OR
      (m.title = 'Audit Logs' AND p.slug = 'audit-logs')
  )
WHERE m.menu_type = 'admin' AND m.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM menu_pages mp WHERE mp.menu_id = m.id AND mp.page_id = p.id AND mp.deleted_at IS NULL
  );
