<?php
declare(strict_types=1);

require_method('GET');

$pdo = db();
$settings = get_contact_form_settings($pdo);
$customFields = list_contact_form_fields($pdo, true);
$publicFields = [];
foreach ($customFields as $field) {
    $publicFields[] = [
        'field_key' => $field['field_key'],
        'label' => $field['label'],
        'field_type' => $field['field_type'],
        'select_source' => $field['select_source'] ?? 'manual',
        'options' => $field['options'],
        'is_required' => $field['is_required'],
        'sort_order' => $field['sort_order'],
    ];
}
$settings['custom_fields'] = $publicFields;
json_ok($settings);
