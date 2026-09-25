-- Custom contact form fields (definitions); physical columns added on create
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS contact_form_fields (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    field_key VARCHAR(64) NOT NULL,
    label VARCHAR(190) NOT NULL,
    field_type VARCHAR(32) NOT NULL DEFAULT 'text',
    options_json TEXT NULL,
    is_required TINYINT(1) NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_contact_form_fields_key (field_key),
    KEY idx_contact_form_fields_visible (is_visible),
    KEY idx_contact_form_fields_sort (sort_order),
    KEY idx_contact_form_fields_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
