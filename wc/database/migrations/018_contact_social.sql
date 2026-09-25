-- Contact & Social: social links + company contact (addresses, phones, summary)
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS social_links (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    icon_class VARCHAR(100) NOT NULL DEFAULT 'fab fa-link',
    link_url VARCHAR(500) NOT NULL,
    label VARCHAR(120) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_social_links_active (is_active),
    KEY idx_social_links_visible (is_visible),
    KEY idx_social_links_sort (sort_order),
    KEY idx_social_links_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS social_link_pages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    social_link_id BIGINT UNSIGNED NOT NULL,
    page_id BIGINT UNSIGNED NOT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    social_link_page_active VARCHAR(64) GENERATED ALWAYS AS (
        IF(deleted_at IS NULL, CONCAT(social_link_id, ':', page_id), NULL)
    ) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uq_social_link_pages_active (social_link_page_active),
    KEY idx_slp_link (social_link_id),
    KEY idx_slp_page (page_id),
    KEY idx_slp_deleted (deleted_at),
    CONSTRAINT fk_slp_link FOREIGN KEY (social_link_id) REFERENCES social_links (id),
    CONSTRAINT fk_slp_page FOREIGN KEY (page_id) REFERENCES pages (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_infos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_summary TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_contact_infos_active (is_active),
    KEY idx_contact_infos_sort (sort_order),
    KEY idx_contact_infos_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_addresses (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    contact_info_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(120) NULL,
    address_text VARCHAR(500) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_contact_addresses_parent (contact_info_id),
    CONSTRAINT fk_contact_addresses_parent FOREIGN KEY (contact_info_id) REFERENCES contact_infos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_phones (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    contact_info_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(120) NULL,
    phone VARCHAR(80) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_contact_phones_parent (contact_info_id),
    CONSTRAINT fk_contact_phones_parent FOREIGN KEY (contact_info_id) REFERENCES contact_infos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_info_pages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    contact_info_id BIGINT UNSIGNED NOT NULL,
    page_id BIGINT UNSIGNED NOT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    contact_info_page_active VARCHAR(64) GENERATED ALWAYS AS (
        IF(deleted_at IS NULL, CONCAT(contact_info_id, ':', page_id), NULL)
    ) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uq_contact_info_pages_active (contact_info_page_active),
    KEY idx_cip_contact (contact_info_id),
    KEY idx_cip_page (page_id),
    KEY idx_cip_deleted (deleted_at),
    CONSTRAINT fk_cip_contact FOREIGN KEY (contact_info_id) REFERENCES contact_infos (id),
    CONSTRAINT fk_cip_page FOREIGN KEY (page_id) REFERENCES pages (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
