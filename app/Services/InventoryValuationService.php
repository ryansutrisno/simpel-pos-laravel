<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrderItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class InventoryValuationService
{
    public const METHOD_FIFO = 'fifo';

    public const METHOD_LIFO = 'lifo';

    public const METHOD_WEIGHTED_AVERAGE = 'weighted_average';

    public function getInventoryValue(
        string $method = self::METHOD_WEIGHTED_AVERAGE,
        ?Carbon $referenceDate = null,
        ?int $categoryId = null
    ): array {
        $referenceDate = $referenceDate ?? now();

        $products = $this->getProductsWithStock($categoryId);

        $items = [];
        $totalQty = 0;
        $totalValue = 0;

        foreach ($products as $product) {
            $cost = $this->getProductCost($product, $method, $referenceDate);
            $itemValue = $product->stock * $cost;

            $items[] = [
                'product' => $product,
                'sku' => $product->sku,
                'category' => $product->category?->name,
                'quantity' => $product->stock,
                'unit_cost' => $cost,
                'total_value' => $itemValue,
            ];

            $totalQty += $product->stock;
            $totalValue += $itemValue;
        }

        // Sort by product name
        usort($items, fn ($a, $b) => $a['product']->name <=> $b['product']->name);

        return [
            'items' => $items,
            'total_products' => count($items),
            'total_quantity' => $totalQty,
            'total_value' => $totalValue,
            'method' => $method,
            'reference_date' => $referenceDate->toDateString(),
            'method_label' => $this->getMethodLabel($method),
        ];
    }

    public function getProductsWithStock(?int $categoryId = null): Collection
    {
        $query = Product::where('is_active', true)
            ->where('stock', '>', 0)
            ->with('category');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return $query->get();
    }

    public function getProductCost(Product $product, string $method, Carbon $referenceDate): float
    {
        return match ($method) {
            self::METHOD_FIFO => $this->getFIFOCost($product, $referenceDate),
            self::METHOD_LIFO => $this->getLIFOCost($product, $referenceDate),
            self::METHOD_WEIGHTED_AVERAGE => $this->getWeightedAverageCost($product, $referenceDate),
            default => $this->getWeightedAverageCost($product, $referenceDate),
        };
    }

    protected function getFIFOCost(Product $product, Carbon $referenceDate): float
    {
        // FIFO: Use the oldest purchase price
        $purchaseItem = PurchaseOrderItem::where('product_id', $product->id)
            ->whereHas('purchaseOrder', function ($q) use ($referenceDate) {
                $q->where('status', 'received')
                    ->where('received_date', '<=', $referenceDate);
            })
            ->orderBy('created_at', 'asc')
            ->first();

        if ($purchaseItem) {
            return (float) $purchaseItem->purchase_price;
        }

        // Fallback to current purchase_price
        return (float) $product->purchase_price;
    }

    protected function getLIFOCost(Product $product, Carbon $referenceDate): float
    {
        // LIFO: Use the newest purchase price
        $purchaseItem = PurchaseOrderItem::where('product_id', $product->id)
            ->whereHas('purchaseOrder', function ($q) use ($referenceDate) {
                $q->where('status', 'received')
                    ->where('received_date', '<=', $referenceDate);
            })
            ->orderBy('created_at', 'desc')
            ->first();

        if ($purchaseItem) {
            return (float) $purchaseItem->purchase_price;
        }

        // Fallback to current purchase_price
        return (float) $product->purchase_price;
    }

    protected function getWeightedAverageCost(Product $product, Carbon $referenceDate): float
    {
        // Weighted Average: (sum of purchase prices) / (sum of quantities)
        $purchaseItems = PurchaseOrderItem::where('product_id', $product->id)
            ->whereHas('purchaseOrder', function ($q) use ($referenceDate) {
                $q->where('status', 'received')
                    ->where('received_date', '<=', $referenceDate);
            })
            ->get();

        if ($purchaseItems->isEmpty()) {
            // Fallback to current purchase_price
            return (float) $product->purchase_price;
        }

        $totalCost = $purchaseItems->sum(fn ($item) => $item->purchase_price * $item->quantity_received);
        $totalQty = $purchaseItems->sum('quantity_received');

        if ($totalQty == 0) {
            return (float) $product->purchase_price;
        }

        return $totalCost / $totalQty;
    }

    public function getMethodLabel(string $method): string
    {
        return match ($method) {
            self::METHOD_FIFO => 'FIFO (First In First Out)',
            self::METHOD_LIFO => 'LIFO (Last In First Out)',
            self::METHOD_WEIGHTED_AVERAGE => 'Rata-rata Tertimbang',
            default => 'Unknown',
        };
    }

    public function getAvailableMethods(): array
    {
        return [
            self::METHOD_WEIGHTED_AVERAGE => 'Rata-rata Tertimbang (Weighted Average)',
            self::METHOD_FIFO => 'FIFO (First In First Out)',
            self::METHOD_LIFO => 'LIFO (Last In First Out)',
        ];
    }
}
