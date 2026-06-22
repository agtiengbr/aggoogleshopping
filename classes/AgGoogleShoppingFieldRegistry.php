<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

final class AgGoogleShoppingFieldRegistry
{
    /** @var array<string, array{label: string, hint: string, allowed_values: string[]}>|null */
    private static $fields;

    /**
     * @return array<string, array{label: string, hint: string, allowed_values: string[]}>
     */
    public static function all(): array
    {
        if (self::$fields !== null) {
            return self::$fields;
        }

        self::$fields = [
            'color' => [
                'label' => 'Cor',
                'hint' => 'Ex.: Azul, Preto',
                'allowed_values' => [],
            ],
            'size' => [
                'label' => 'Tamanho',
                'hint' => 'Ex.: M, 42, G',
                'allowed_values' => [],
            ],
            'material' => [
                'label' => 'Material',
                'hint' => 'Ex.: Algodão, Couro',
                'allowed_values' => [],
            ],
            'pattern' => [
                'label' => 'Estampa',
                'hint' => 'Ex.: Liso, Listrado',
                'allowed_values' => [],
            ],
            'gender' => [
                'label' => 'Gênero',
                'hint' => 'male, female ou unisex',
                'allowed_values' => [
                    'male' => 'Masculino (male)',
                    'female' => 'Feminino (female)',
                    'unisex' => 'Unissex (unisex)',
                ],
            ],
            'age_group' => [
                'label' => 'Faixa etária',
                'hint' => 'newborn, infant, toddler, kids ou adult',
                'allowed_values' => [
                    'newborn' => 'Recém-nascido (newborn)',
                    'infant' => 'Bebê (infant)',
                    'toddler' => 'Criança pequena (toddler)',
                    'kids' => 'Criança (kids)',
                    'adult' => 'Adulto (adult)',
                ],
            ],
            'google_product_category' => [
                'label' => 'Categoria Google',
                'hint' => 'Busque na taxonomia Google (ex.: Roupas, Eletrônicos)',
                'allowed_values' => [],
            ],
            'product_type' => [
                'label' => 'Tipo de produto',
                'hint' => 'Ex.: Roupas > Camisetas',
                'allowed_values' => [],
            ],
        ];

        return self::$fields;
    }

    /**
     * @return string[]
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function isValid(string $googleField): bool
    {
        return isset(self::all()[$googleField]);
    }

    /**
     * @return string[]
     */
    public static function allowedValues(string $googleField): array
    {
        return array_keys(self::getFixedValueOptionMap($googleField));
    }

    /**
     * @return array<string, string>
     */
    public static function getFixedValueOptionMap(string $googleField): array
    {
        $fields = self::all();
        $options = $fields[$googleField]['allowed_values'] ?? [];

        return is_array($options) ? $options : [];
    }

    public static function hasFixedValueOptions(string $googleField): bool
    {
        return self::getFixedValueOptionMap($googleField) !== [];
    }

    public static function usesGoogleTaxonomy(string $googleField): bool
    {
        return $googleField === 'google_product_category';
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function getFixedValueOptions(string $googleField): array
    {
        $options = [['value' => '', 'label' => '—']];
        foreach (self::getFixedValueOptionMap($googleField) as $value => $label) {
            $options[] = [
                'value' => (string) $value,
                'label' => (string) $label,
            ];
        }

        return $options;
    }

    public static function getFixedValueLabel(string $googleField, string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $options = self::getFixedValueOptionMap($googleField);

        return $options[$value] ?? $value;
    }

    public static function normalizeValue(string $googleField, string $value): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
        if ($value === '') {
            return '';
        }

        $allowed = self::allowedValues($googleField);
        if ($allowed === []) {
            return $value;
        }

        $optionMap = self::getFixedValueOptionMap($googleField);
        $lower = Tools::strtolower($value);
        foreach ($allowed as $canonical) {
            if ($lower === Tools::strtolower($canonical)) {
                return $canonical;
            }
        }

        foreach ($optionMap as $canonical => $label) {
            if ($lower === Tools::strtolower($label)) {
                return $canonical;
            }
        }

        return $value;
    }
}
