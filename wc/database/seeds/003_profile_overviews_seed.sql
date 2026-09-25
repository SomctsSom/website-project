-- Idempotent seeds for Profile Overview module
SET NAMES utf8mb4;
SET time_zone = '+00:00';

INSERT INTO permissions (module, action, code, description, is_system, created_at)
SELECT m.module, m.action, CONCAT(m.module, '.', m.action), m.description, 1, UTC_TIMESTAMP()
FROM (
    SELECT 'profile_overviews' AS module, 'view' AS action, 'View profile overviews' AS description UNION ALL
    SELECT 'profile_overviews', 'create', 'Create profile overviews' UNION ALL
    SELECT 'profile_overviews', 'edit', 'Edit profile overviews' UNION ALL
    SELECT 'profile_overviews', 'delete', 'Soft-delete profile overviews' UNION ALL
    SELECT 'profile_overviews', 'restore', 'Restore profile overviews'
) AS m
WHERE NOT EXISTS (
    SELECT 1 FROM permissions p WHERE p.code_active = CONCAT(m.module, '.', m.action)
);

INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, UTC_TIMESTAMP()
FROM roles r
CROSS JOIN permissions p
WHERE r.code_active = 'super_admin'
  AND p.code LIKE 'profile_overviews.%'
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
  AND p.code LIKE 'profile_overviews.%'
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
  AND p.code IN ('profile_overviews.view', 'profile_overviews.create', 'profile_overviews.edit')
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.role_id = r.id AND rp.permission_id = p.id AND rp.deleted_at IS NULL
  );

INSERT INTO pages (page_type, title, slug, template_key, is_active, created_at)
SELECT 'admin', 'Profile Overviews', 'profile-overviews', 'profile_overviews', 1, UTC_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM pages p WHERE p.type_slug_active = 'admin:profile-overviews'
);

INSERT INTO menus (menu_type, parent_menu_id, title, icon, url, sort_order, is_active, created_at)
SELECT 'admin', parent.id, 'Profile Overviews', NULL, '/profile-overviews', 30, 1, UTC_TIMESTAMP()
FROM menus parent
WHERE parent.menu_type = 'admin'
  AND parent.title = 'Website Content'
  AND parent.parent_menu_id IS NULL
  AND parent.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM menus m
      WHERE m.menu_type = 'admin' AND m.title = 'Profile Overviews' AND m.deleted_at IS NULL
  );

INSERT INTO menu_pages (menu_id, page_id, sort_order, created_at)
SELECT m.id, p.id, 0, UTC_TIMESTAMP()
FROM menus m
JOIN pages p ON p.page_type = 'admin' AND p.slug = 'profile-overviews' AND p.deleted_at IS NULL
WHERE m.menu_type = 'admin' AND m.title = 'Profile Overviews' AND m.deleted_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM menu_pages mp WHERE mp.menu_id = m.id AND mp.page_id = p.id AND mp.deleted_at IS NULL
  );
