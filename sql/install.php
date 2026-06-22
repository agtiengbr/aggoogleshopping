<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

return [
    'CREATE TABLE IF NOT EXISTS `_DB_PREFIX_aggoogleshopping_category_field_map` (
        `id_category` INT UNSIGNED NOT NULL,
        `google_field` VARCHAR(64) NOT NULL,
        `id_attribute_group` INT UNSIGNED NOT NULL DEFAULT 0,
        `id_feature` INT UNSIGNED NOT NULL DEFAULT 0,
        `fixed_value` VARCHAR(255) NOT NULL DEFAULT \'\',
        `id_shop` INT UNSIGNED NOT NULL,
        `date_upd` DATETIME NOT NULL,
        PRIMARY KEY (`id_category`, `google_field`, `id_shop`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
];
