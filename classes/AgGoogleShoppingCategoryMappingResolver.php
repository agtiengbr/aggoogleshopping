<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

final class AgGoogleShoppingCategoryMappingResolver
{
    /** @var array<int, array<string, array<string, mixed>>> */
    private $directMappings;

    /** @var array<string, array<string, mixed>|null> */
    private $effectiveCache = [];

    /** @var array<int, int> */
    private $parentCache = [];

    /**
     * @param array<int, array<string, array<string, mixed>>> $directMappings
     */
    public function __construct(array $directMappings)
    {
        $this->directMappings = $directMappings;
    }

    public static function createForShop(int $idShop): self
    {
        $repository = new AgGoogleShoppingCategoryMappingRepository();

        return new self($repository->getAllForShop($idShop));
    }

    /**
     * @return array{mapping: array<string, mixed>|null, source_category_id: int}
     */
    public function resolveEffective(int $idCategory, string $googleField): array
    {
        $cacheKey = $idCategory . ':' . $googleField;
        if (array_key_exists($cacheKey, $this->effectiveCache)) {
            $mapping = $this->effectiveCache[$cacheKey];

            return [
                'mapping' => $mapping,
                'source_category_id' => $mapping !== null ? (int) $mapping['id_category'] : 0,
            ];
        }

        $categoryId = $idCategory;
        while ($categoryId > 0) {
            if (isset($this->directMappings[$categoryId][$googleField])) {
                $mapping = $this->directMappings[$categoryId][$googleField];
                $this->effectiveCache[$cacheKey] = $mapping;

                return [
                    'mapping' => $mapping,
                    'source_category_id' => $categoryId,
                ];
            }

            $categoryId = $this->getParentCategoryId($categoryId);
        }

        $this->effectiveCache[$cacheKey] = null;

        return [
            'mapping' => null,
            'source_category_id' => 0,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolveEffectiveMapping(int $idCategory, string $googleField)
    {
        $resolved = $this->resolveEffective($idCategory, $googleField);

        return $resolved['mapping'];
    }

    private function getParentCategoryId(int $idCategory): int
    {
        if (isset($this->parentCache[$idCategory])) {
            return $this->parentCache[$idCategory];
        }

        $category = new Category($idCategory);
        if (!Validate::isLoadedObject($category)) {
            $this->parentCache[$idCategory] = 0;

            return 0;
        }

        $idParent = (int) $category->id_parent;
        if ($idParent <= 0 || $idParent === $idCategory) {
            $this->parentCache[$idCategory] = 0;

            return 0;
        }

        $this->parentCache[$idCategory] = $idParent;

        return $idParent;
    }
}
