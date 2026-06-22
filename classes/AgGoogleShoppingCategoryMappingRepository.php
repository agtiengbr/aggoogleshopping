<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

final class AgGoogleShoppingCategoryMappingRepository
{
    /**
     * @return array<int, array<string, array<string, mixed>>>
     */
    public function getAllForShop(int $idShop): array
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `id_category`, `google_field`, `id_attribute_group`, `id_feature`, `fixed_value`
            FROM `' . _DB_PREFIX_ . 'aggoogleshopping_category_field_map`
            WHERE `id_shop` = ' . (int) $idShop
        );

        if (!is_array($rows)) {
            return [];
        }

        $indexed = [];
        foreach ($rows as $row) {
            $idCategory = (int) $row['id_category'];
            $googleField = (string) $row['google_field'];
            $indexed[$idCategory][$googleField] = $this->normalizeRow($row);
        }

        return $indexed;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findDirect(int $idCategory, string $googleField, int $idShop)
    {
        if (!AgGoogleShoppingFieldRegistry::isValid($googleField)) {
            return null;
        }

        $row = Db::getInstance()->getRow(
            'SELECT `id_category`, `google_field`, `id_attribute_group`, `id_feature`, `fixed_value`
            FROM `' . _DB_PREFIX_ . 'aggoogleshopping_category_field_map`
            WHERE `id_category` = ' . (int) $idCategory . '
            AND `google_field` = \'' . pSQL($googleField) . '\'
            AND `id_shop` = ' . (int) $idShop
        );

        if (!is_array($row)) {
            return null;
        }

        return $this->normalizeRow($row);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function findAllDirectForCategory(int $idCategory, int $idShop): array
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `id_category`, `google_field`, `id_attribute_group`, `id_feature`, `fixed_value`
            FROM `' . _DB_PREFIX_ . 'aggoogleshopping_category_field_map`
            WHERE `id_category` = ' . (int) $idCategory . '
            AND `id_shop` = ' . (int) $idShop
        );

        if (!is_array($rows)) {
            return [];
        }

        $mapped = [];
        foreach ($rows as $row) {
            $mapped[(string) $row['google_field']] = $this->normalizeRow($row);
        }

        return $mapped;
    }

    public function saveField(
        int $idCategory,
        string $googleField,
        int $idAttributeGroup,
        int $idFeature,
        string $fixedValue,
        int $idShop
    ): bool {
        if (!AgGoogleShoppingFieldRegistry::isValid($googleField)) {
            return false;
        }

        if ($idAttributeGroup <= 0 && $idFeature <= 0 && trim($fixedValue) === '') {
            return $this->deleteField($idCategory, $googleField, $idShop);
        }

        return Db::getInstance()->insert(
            'aggoogleshopping_category_field_map',
            [
                'id_category' => (int) $idCategory,
                'google_field' => pSQL($googleField),
                'id_attribute_group' => max(0, $idAttributeGroup),
                'id_feature' => max(0, $idFeature),
                'fixed_value' => pSQL(Tools::substr(trim($fixedValue), 0, 255)),
                'id_shop' => (int) $idShop,
                'date_upd' => date('Y-m-d H:i:s'),
            ],
            false,
            true,
            Db::REPLACE
        );
    }

    public function deleteField(int $idCategory, string $googleField, int $idShop): bool
    {
        return Db::getInstance()->delete(
            'aggoogleshopping_category_field_map',
            '`id_category` = ' . (int) $idCategory . '
            AND `google_field` = \'' . pSQL($googleField) . '\'
            AND `id_shop` = ' . (int) $idShop
        );
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        return [
            'id_category' => (int) $row['id_category'],
            'google_field' => (string) $row['google_field'],
            'id_attribute_group' => (int) $row['id_attribute_group'],
            'id_feature' => (int) $row['id_feature'],
            'fixed_value' => (string) $row['fixed_value'],
        ];
    }
}
