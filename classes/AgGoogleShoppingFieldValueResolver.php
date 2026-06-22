<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

final class AgGoogleShoppingFieldValueResolver
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private $featuresCache = [];

    public function resolve(
        Product $product,
        int $idProductAttribute,
        int $langId,
        string $googleField,
        ?array $mapping
    ): string {
        if ($mapping === null) {
            return '';
        }

        $value = $this->resolveFromAttribute($product, $idProductAttribute, $langId, $mapping);
        if ($value === '' && (int) $mapping['id_feature'] > 0) {
            $value = $this->resolveFromFeature((int) $product->id, $langId, (int) $mapping['id_feature']);
        }
        if ($value === '' && trim((string) $mapping['fixed_value']) !== '') {
            $value = trim((string) $mapping['fixed_value']);
        }

        if ($value === '') {
            return '';
        }

        return AgGoogleShoppingFieldRegistry::normalizeValue($googleField, $value);
    }

    /**
     * @param array<string, mixed> $mapping
     */
    private function resolveFromAttribute(
        Product $product,
        int $idProductAttribute,
        int $langId,
        array $mapping
    ): string {
        $idAttributeGroup = (int) $mapping['id_attribute_group'];
        if ($idAttributeGroup <= 0 || $idProductAttribute <= 0) {
            return '';
        }

        $attributes = Product::getAttributesParams((int) $product->id, $idProductAttribute);
        if (!is_array($attributes)) {
            return '';
        }

        foreach ($attributes as $attribute) {
            if ((int) ($attribute['id_attribute_group'] ?? 0) !== $idAttributeGroup) {
                continue;
            }

            return trim((string) ($attribute['name'] ?? ''));
        }

        return '';
    }

    private function resolveFromFeature(int $idProduct, int $langId, int $idFeature): string
    {
        $features = $this->getProductFeatures($idProduct, $langId);
        foreach ($features as $feature) {
            if ((int) ($feature['id_feature'] ?? 0) !== $idFeature) {
                continue;
            }

            return trim((string) ($feature['value'] ?? ''));
        }

        return '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getProductFeatures(int $idProduct, int $langId): array
    {
        $cacheKey = $idProduct . '-' . $langId;
        if (!isset($this->featuresCache[$cacheKey])) {
            $features = Product::getFrontFeaturesStatic($langId, $idProduct);
            $this->featuresCache[$cacheKey] = is_array($features) ? $features : [];
        }

        return $this->featuresCache[$cacheKey];
    }
}
