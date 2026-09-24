-- Vision & Mission module (CRUD + multi-page assignment)
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS vision_missions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    statement_type VARCHAR(20) NOT NULL DEFAULT 'vision',
    title VARCHAR(200) NOT NULL,
    body TEXT NULL,
    image_path VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_vision_missions_type (statement_type),
    KEY idx_vision_missions_active (is_active),
    KEY idx_vision_missions_sort (sort_order),
    KEY idx_vision_missions_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vision_mission_pages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    vision_mission_id BIGINT UNSIGNED NOT NULL,
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
    vision_page_active VARCHAR(64) GENERATED ALWAYS AS (
        IF(deleted_at IS NULL, CONCAT(vision_mission_id, ':', page_id), NULL)
    ) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uq_vision_mission_pages_active (vision_page_active),
    KEY idx_vmp_vision (vision_mission_id),
    KEY idx_vmp_page (page_id),
    KEY idx_vmp_featured (is_featured),
    KEY idx_vmp_active (is_active),
    KEY idx_vmp_deleted (deleted_at),
    CONSTRAINT fk_vmp_vision FOREIGN KEY (vision_mission_id) REFERENCES vision_missions (id),
    CONSTRAINT fk_vmp_page FOREIGN KEY (page_id) REFERENCES pages (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
