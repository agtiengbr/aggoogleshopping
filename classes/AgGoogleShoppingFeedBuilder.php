<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

final class AgGoogleShoppingFeedBuilder
{
    private const MAX_ADDITIONAL_IMAGES = 10;

    /** @var string */
    private $skuField;

    /** @var Context */
    private $context;

    /** @var AgGoogleShoppingCategoryMappingResolver|null */
    private $mappingResolver;

    /** @var AgGoogleShoppingFieldValueResolver|null */
    private $fieldValueResolver;

    public function __construct(string $skuField, Context $context)
    {
        $this->skuField = $skuField;
        $this->context = $context;
    }

    public function build(): string
    {
        $link = $this->context->link;
        $currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));
        $currencyIso = $currency->iso_code ?: 'BRL';
        $langId = (int) $this->context->language->id;
        $idShop = (int) ($this->context->shop->id ?: Configuration::get('PS_SHOP_DEFAULT'));
        $this->mappingResolver = AgGoogleShoppingCategoryMappingResolver::createForShop($idShop);
        $this->fieldValueResolver = new AgGoogleShoppingFieldValueResolver();

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $rss = $dom->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
        $dom->appendChild($rss);

        $channel = $dom->createElement('channel');
        $rss->appendChild($channel);

        $channel->appendChild($dom->createElement('title', Configuration::get('PS_SHOP_NAME') ?: 'Shop'));
        $channel->appendChild($dom->createElement('link', Tools::getShopDomainSsl(true)));
        $channel->appendChild($dom->createElement('description', 'Google Shopping product feed'));
        $channel->appendChild($dom->createElement('lastBuildDate', date(DATE_RSS)));

        $products = Product::getProducts($langId, 0, 0, 'id_product', 'ASC', false, true);

        foreach ($products as $row) {
            $product = new Product((int) $row['id_product'], false, $langId);
            if (!Validate::isLoadedObject($product)) {
                continue;
            }

            $combinationRows = Product::getProductAttributesIds((int) $product->id, true);
            if ($combinationRows === []) {
                $this->appendItem($dom, $channel, $product, 0, $langId, $link, $currencyIso);

                continue;
            }

            foreach ($combinationRows as $combinationRow) {
                $idProductAttribute = (int) ($combinationRow['id_product_attribute'] ?? 0);
                if ($idProductAttribute <= 0) {
                    continue;
                }

                $this->appendItem($dom, $channel, $product, $idProductAttribute, $langId, $link, $currencyIso);
            }
        }

        return $dom->saveXML() ?: '';
    }

    private function appendItem(
        DOMDocument $dom,
        DOMElement $channel,
        Product $product,
        int $idProductAttribute,
        int $langId,
        Link $link,
        string $currencyIso
    ): void {
        $sku = $this->resolveSku($product, $idProductAttribute);
        if ($sku === '') {
            return;
        }

        $availability = AgGoogleShoppingAvailabilityResolver::resolve($product, $idProductAttribute);
        $price = (float) Product::getPriceStatic((int) $product->id, true, $idProductAttribute);
        if ($price <= 0) {
            return;
        }

        $mainImageId = $this->resolveMainImageId($product, $langId, $idProductAttribute);
        $imageLink = $this->buildImageUrl($product, $link, $mainImageId);
        $additionalImageLinks = $this->resolveAdditionalImageLinks($product, $link, $langId, $mainImageId, $idProductAttribute);
        $gtin = $this->resolveGtin($product, $idProductAttribute);

        $item = $dom->createElement('item');
        $this->appendG($dom, $item, 'id', $sku);
        $this->appendG($dom, $item, 'item_group_id', $this->resolveItemGroupId($product));
        $this->appendG($dom, $item, 'title', $this->buildTitle($product, $idProductAttribute, $langId));
        $this->appendG($dom, $item, 'description', $this->cleanText(strip_tags($product->description_short ?: $product->description)));
        $this->appendG(
            $dom,
            $item,
            'link',
            $link->getProductLink($product, null, null, null, $langId, null, $idProductAttribute > 0 ? $idProductAttribute : null)
        );
        if ($imageLink !== '') {
            $this->appendG($dom, $item, 'image_link', $imageLink);
        }
        foreach ($additionalImageLinks as $additionalImageLink) {
            $this->appendG($dom, $item, 'additional_image_link', $additionalImageLink);
        }
        $this->appendG($dom, $item, 'availability', $availability['availability']);
        $this->appendG($dom, $item, 'price', number_format($price, 2, '.', '') . ' ' . $currencyIso);
        $this->appendG($dom, $item, 'condition', 'new');

        $brand = Manufacturer::getNameById((int) $product->id_manufacturer);
        if (is_string($brand) && $brand !== '') {
            $this->appendG($dom, $item, 'brand', $brand);
        }

        if ($gtin !== '') {
            $this->appendG($dom, $item, 'gtin', $gtin);
        }

        $this->appendMappedGoogleFields($dom, $item, $product, $idProductAttribute, $langId);

        $channel->appendChild($item);
    }

    private function buildTitle(Product $product, int $idProductAttribute, int $langId): string
    {
        $baseName = trim((string) $product->name);
        if ($idProductAttribute <= 0) {
            return $this->cleanText($baseName);
        }

        $attributes = Product::getAttributesParams((int) $product->id, $idProductAttribute);
        $parts = [];
        foreach ($attributes as $attribute) {
            $group = trim((string) ($attribute['group'] ?? ''));
            $value = trim((string) ($attribute['name'] ?? ''));
            if ($group !== '' && $value !== '') {
                $parts[] = $group . ': ' . $value;
            }
        }

        if ($parts === []) {
            return $this->cleanText($baseName);
        }

        return $this->cleanText($baseName . ' ' . implode('; ', $parts));
    }

    private function resolveItemGroupId(Product $product): string
    {
        $parentReference = trim((string) $product->reference);

        return $parentReference !== '' ? $parentReference : (string) $product->id;
    }

    private function resolveSku(Product $product, int $idProductAttribute = 0): string
    {
        $combination = $this->loadCombination($idProductAttribute);

        switch ($this->skuField) {
            case 'ean13':
                if ($combination !== null && $combination->ean13 !== '') {
                    return (string) $combination->ean13;
                }

                return (string) ($product->ean13 ?: $product->reference);
            case 'upc':
                if ($combination !== null && $combination->upc !== '') {
                    return (string) $combination->upc;
                }

                return (string) ($product->upc ?: $product->reference);
            case 'id':
                return $idProductAttribute > 0 ? (string) $idProductAttribute : (string) $product->id;
            default:
                if ($combination !== null && $combination->reference !== '') {
                    return (string) $combination->reference;
                }
                if ($idProductAttribute > 0) {
                    $parentReference = trim((string) $product->reference);

                    return $parentReference !== ''
                        ? $parentReference . '-' . $idProductAttribute
                        : (string) $idProductAttribute;
                }

                return (string) $product->reference;
        }
    }

    private function resolveGtin(Product $product, int $idProductAttribute): string
    {
        $combination = $this->loadCombination($idProductAttribute);
        if ($combination !== null && $combination->ean13 !== '') {
            return (string) $combination->ean13;
        }

        return (string) $product->ean13;
    }

    /**
     * @return Combination|null
     */
    private function loadCombination(int $idProductAttribute)
    {
        if ($idProductAttribute <= 0) {
            return null;
        }

        $combination = new Combination($idProductAttribute);
        if (!Validate::isLoadedObject($combination)) {
            return null;
        }

        return $combination;
    }

    private function resolveMainImageId(Product $product, int $langId, int $idProductAttribute = 0): int
    {
        $idImage = 0;

        if ($idProductAttribute > 0) {
            $combinationImages = $product->getCombinationImages($langId);
            if (
                is_array($combinationImages)
                && isset($combinationImages[$idProductAttribute][0]['id_image'])
            ) {
                $idImage = (int) $combinationImages[$idProductAttribute][0]['id_image'];
            }
        }

        if ($idImage <= 0) {
            $cover = Product::getCover((int) $product->id);
            if (is_array($cover) && isset($cover['id_image'])) {
                $idImage = (int) $cover['id_image'];
            }
        }

        return $idImage;
    }

    private function buildImageUrl(Product $product, Link $link, int $idImage): string
    {
        if ($idImage <= 0) {
            return '';
        }

        return $link->getImageLink($product->link_rewrite, $idImage, $this->getImageTypeName('large'));
    }

    /**
     * @return string[]
     */
    private function resolveAdditionalImageLinks(
        Product $product,
        Link $link,
        int $langId,
        int $mainImageId,
        int $idProductAttribute = 0
    ): array {
        $orderedImageIds = [];

        if ($idProductAttribute > 0) {
            $combinationImages = $product->getCombinationImages($langId);
            if (is_array($combinationImages) && isset($combinationImages[$idProductAttribute])) {
                foreach ($combinationImages[$idProductAttribute] as $index => $row) {
                    if ($index === 0) {
                        continue;
                    }

                    $idImage = (int) ($row['id_image'] ?? 0);
                    if ($idImage > 0) {
                        $orderedImageIds[] = $idImage;
                    }
                }
            }
        }

        $images = $product->getImages($langId);
        if (is_array($images)) {
            foreach ($images as $image) {
                $idImage = (int) ($image['id_image'] ?? 0);
                if ($idImage > 0) {
                    $orderedImageIds[] = $idImage;
                }
            }
        }

        $seen = $mainImageId > 0 ? [$mainImageId => true] : [];
        $links = [];

        foreach ($orderedImageIds as $idImage) {
            if (isset($seen[$idImage])) {
                continue;
            }

            $seen[$idImage] = true;
            $imageUrl = $this->buildImageUrl($product, $link, $idImage);
            if ($imageUrl === '') {
                continue;
            }

            $links[] = $imageUrl;
            if (count($links) >= self::MAX_ADDITIONAL_IMAGES) {
                break;
            }
        }

        return $links;
    }

    private function appendMappedGoogleFields(
        DOMDocument $dom,
        DOMElement $item,
        Product $product,
        int $idProductAttribute,
        int $langId
    ): void {
        if ($this->mappingResolver === null || $this->fieldValueResolver === null) {
            return;
        }

        $idCategory = (int) $product->id_category_default;
        if ($idCategory <= 0) {
            return;
        }

        foreach (AgGoogleShoppingFieldRegistry::keys() as $googleField) {
            $mapping = $this->mappingResolver->resolveEffectiveMapping($idCategory, $googleField);
            $value = $this->fieldValueResolver->resolve(
                $product,
                $idProductAttribute,
                $langId,
                $googleField,
                $mapping
            );
            if ($value !== '') {
                $this->appendG($dom, $item, $googleField, $value);
            }
        }
    }

    private function appendG(DOMDocument $dom, DOMElement $item, string $name, string $value): void
    {
        if ($value === '') {
            return;
        }

        $node = $dom->createElement('g:' . $name);
        $node->appendChild($dom->createTextNode($value));
        $item->appendChild($node);
    }

    private function cleanText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return Tools::substr(trim($text), 0, 5000);
    }

    private function getImageTypeName($name)
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            return ImageType::getFormattedName($name);
        }

        return ImageType::getFormatedName($name);
    }
}
