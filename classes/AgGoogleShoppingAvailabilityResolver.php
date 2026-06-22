<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

final class AgGoogleShoppingAvailabilityResolver
{
    /**
     * @return array{
     *     availability: string,
     *     in_stock: bool,
     *     active: bool,
     *     quantity: int,
     *     allow_backorder: bool,
     *     available_date: ?string
     * }
     */
    public static function resolve(Product $product, int $idProductAttribute = 0): array
    {
        $active = (bool) $product->active;
        $availableForOrder = (bool) $product->available_for_order;
        $quantity = (int) StockAvailable::getQuantityAvailableByProduct(
            (int) $product->id,
            $idProductAttribute
        );

        $stockRow = StockAvailable::getStockAvailableIdByProductId(
            (int) $product->id,
            $idProductAttribute
        );
        $stock = new StockAvailable((int) $stockRow);
        $allowBackorder = self::allowsBackorder($product, $stock);

        $availableDateRaw = (string) ($product->available_date ?? '');
        $availableDate = null;
        if ($availableDateRaw !== '' && $availableDateRaw !== '0000-00-00') {
            $availableDate = $availableDateRaw;
        }

        $isPreorder = $availableDate !== null && strtotime($availableDate) > time();

        if (!$active || !$availableForOrder) {
            return self::result('out_of_stock', false, $active && $availableForOrder, $quantity, $allowBackorder, $availableDate);
        }

        if ($isPreorder) {
            return self::result('preorder', $allowBackorder || true, true, $quantity, $allowBackorder, $availableDate);
        }

        if ($quantity > 0) {
            return self::result('in_stock', true, true, $quantity, $allowBackorder, $availableDate);
        }

        if ($allowBackorder) {
            return self::result('backorder', true, true, $quantity, true, $availableDate);
        }

        return self::result('out_of_stock', false, true, $quantity, false, $availableDate);
    }

    private static function allowsBackorder(Product $product, StockAvailable $stock): bool
    {
        $policy = (int) $stock->out_of_stock;
        if ($policy === 1) {
            return true;
        }
        if ($policy === 0) {
            return false;
        }

        return (bool) Configuration::get('PS_ORDER_OUT_OF_STOCK');
    }

    /**
     * @return array{
     *     availability: string,
     *     in_stock: bool,
     *     active: bool,
     *     quantity: int,
     *     allow_backorder: bool,
     *     available_date: ?string
     * }
     */
    private static function result(
        string $availability,
        bool $inStock,
        bool $active,
        int $quantity,
        bool $allowBackorder,
        ?string $availableDate
    ): array {
        return [
            'availability' => $availability,
            'in_stock' => $inStock,
            'active' => $active,
            'quantity' => $quantity,
            'allow_backorder' => $allowBackorder,
            'available_date' => $availableDate,
        ];
    }
}
