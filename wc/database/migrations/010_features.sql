-- Features module (section + cards + multi-page assignment)
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS features (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_features_active (is_active),
    KEY idx_features_sort (sort_order),
    KEY idx_features_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS feature_cards (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    feature_id BIGINT UNSIGNED NOT NULL,
    icon_key VARCHAR(40) NOT NULL DEFAULT 'star',
    icon_path VARCHAR(255) NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_feature_cards_feature (feature_id),
    KEY idx_feature_cards_sort (feature_id, sort_order),
    KEY idx_feature_cards_active (is_active),
    KEY idx_feature_cards_deleted (deleted_at),
    CONSTRAINT fk_feature_cards_feature FOREIGN KEY (feature_id) REFERENCES features (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS feature_pages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    feature_id BIGINT UNSIGNED NOT NULL,
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
    feature_page_active VARCHAR(64) GENERATED ALWAYS AS (
        IF(deleted_at IS NULL, CONCAT(feature_id, ':', page_id), NULL)
    ) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uq_feature_pages_active (feature_page_active),
    KEY idx_fp_feature (feature_id),
    KEY idx_fp_page (page_id),
    KEY idx_fp_featured (is_featured),
    KEY idx_fp_active (is_active),
    KEY idx_fp_deleted (deleted_at),
    CONSTRAINT fk_fp_feature FOREIGN KEY (feature_id) REFERENCES features (id),
    CONSTRAINT fk_fp_page FOREIGN KEY (page_id) REFERENCES pages (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
