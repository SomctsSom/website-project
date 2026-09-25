-- Select source mode for custom contact form fields (manual options vs connected table)
SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE contact_form_fields
    ADD COLUMN select_source VARCHAR(16) NOT NULL DEFAULT 'manual' AFTER field_type,
    ADD COLUMN source_table VARCHAR(64) NULL AFTER select_source,
    ADD COLUMN source_value_column VARCHAR(64) NULL AFTER source_table,
    ADD COLUMN source_label_column VARCHAR(64) NULL AFTER source_value_column;

UPDATE contact_form_fields
SET select_source = 'manual'
WHERE select_source IS NULL OR select_source = '';
