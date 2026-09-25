-- Testimonials module (CRUD + multi-page assignment)
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS testimonials (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
    quote TEXT NOT NULL,
    author_name VARCHAR(150) NOT NULL,
    author_role VARCHAR(200) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    deleted_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_testimonials_active (is_active),
    KEY idx_testimonials_sort (sort_order),
    KEY idx_testimonials_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS testimonial_pages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    testimonial_id BIGINT UNSIGNED NOT NULL,
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
    testimonial_page_active VARCHAR(64) GENERATED ALWAYS AS (
        IF(deleted_at IS NULL, CONCAT(testimonial_id, ':', page_id), NULL)
    ) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uq_testimonial_pages_active (testimonial_page_active),
    KEY idx_tp_testimonial (testimonial_id),
    KEY idx_tp_page (page_id),
    KEY idx_tp_featured (is_featured),
    KEY idx_tp_active (is_active),
    KEY idx_tp_deleted (deleted_at),
    CONSTRAINT fk_tp_testimonial FOREIGN KEY (testimonial_id) REFERENCES testimonials (id),
    CONSTRAINT fk_tp_page FOREIGN KEY (page_id) REFERENCES pages (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
