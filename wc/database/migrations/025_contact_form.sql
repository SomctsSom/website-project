-- Contact form inquiries + field visibility defaults
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS contact_inquiries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(190) NULL,
    company_name VARCHAR(190) NULL,
    phone VARCHAR(80) NULL,
    email VARCHAR(190) NULL,
    preferred_service_id BIGINT UNSIGNED NULL,
    preferred_service_title VARCHAR(255) NULL,
    message TEXT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'new',
    page_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_contact_inquiries_status (status),
    KEY idx_contact_inquiries_created (created_at),
    KEY idx_contact_inquiries_deleted (deleted_at),
    KEY idx_contact_inquiries_service (preferred_service_id),
    KEY idx_contact_inquiries_page (page_id),
    CONSTRAINT fk_contact_inquiries_service FOREIGN KEY (preferred_service_id) REFERENCES services (id) ON DELETE SET NULL,
    CONSTRAINT fk_contact_inquiries_page FOREIGN KEY (page_id) REFERENCES pages (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO site_settings (setting_key, setting_value, updated_at)
SELECT s.setting_key, s.setting_value, UTC_TIMESTAMP()
FROM (
    SELECT 'contact_form_show_full_name' AS setting_key, '1' AS setting_value UNION ALL
    SELECT 'contact_form_show_company_name', '1' UNION ALL
    SELECT 'contact_form_show_phone', '1' UNION ALL
    SELECT 'contact_form_show_email', '1' UNION ALL
    SELECT 'contact_form_show_preferred_service', '1' UNION ALL
    SELECT 'contact_form_show_message', '1'
) AS s
WHERE NOT EXISTS (
    SELECT 1 FROM site_settings x WHERE x.setting_key = s.setting_key
);
