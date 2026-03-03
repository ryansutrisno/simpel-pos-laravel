<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Support\Collection;

class VariantService
{
    public static function getAvailableVariants(int $productId): Collection
    {
        return ProductVariant::where('product_id', $productId)
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('variant_name')
            ->get();
    }

    public static function getAllVariants(int $productId): Collection
    {
        return ProductVariant::where('product_id', $productId)
            ->orderBy('variant_name')
            ->get();
    }

    public static function getVariantBySku(string $sku): ?ProductVariant
    {
        return ProductVariant::where('sku', $sku)
            ->where('is_active', true)
            ->first();
    }

    public static function checkStock(int $variantId, int $quantity): bool
    {
        $variant = ProductVariant::find($variantId);

        if (! $variant) {
            return false;
        }

        return $variant->stock >= $quantity;
    }

    public static function decrementStock(int $variantId, int $quantity): bool
    {
        $variant = ProductVariant::find($variantId);

        if (! $variant || $variant->stock < $quantity) {
            return false;
        }

        $variant->decrement('stock', $quantity);

        return true;
    }

    public static function incrementStock(int $variantId, int $quantity): bool
    {
        $variant = ProductVariant::find($variantId);

        if (! $variant) {
            return false;
        }

        $variant->increment('stock', $quantity);

        return true;
    }

    public static function getSellingPrice(int $variantId): ?float
    {
        $variant = ProductVariant::find($variantId);

        return $variant ? (float) $variant->selling_price : null;
    }

    public static function getPurchasePrice(int $variantId): ?float
    {
        $variant = ProductVariant::find($variantId);

        return $variant ? (float) $variant->purchase_price : null;
    }

    public static function hasVariants(int $productId): bool
    {
        return ProductVariant::where('product_id', $productId)
            ->where('is_active', true)
            ->exists();
    }

    public static function createVariant(array $data): ProductVariant
    {
        return ProductVariant::create($data);
    }

    public static function updateVariant(int $variantId, array $data): ?ProductVariant
    {
        $variant = ProductVariant::find($variantId);

        if (! $variant) {
            return null;
        }

        $variant->update($data);

        return $variant->fresh();
    }

    public static function getLowStockVariants(): Collection
    {
        return ProductVariant::with('product')
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'low_stock_threshold')
            ->get();
    }

    public static function getVariantStockValue(int $variantId): float
    {
        $variant = ProductVariant::find($variantId);

        if (! $variant) {
            return 0.0;
        }

        return (float) ($variant->stock * $variant->purchase_price);
    }
}
