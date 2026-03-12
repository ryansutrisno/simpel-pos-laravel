<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockHistory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function __construct(protected CurrentStoreService $currentStoreService) {}

    public function addStock(
        Product $product,
        int $quantity,
        StockMovementType $type = StockMovementType::In,
        ?object $reference = null,
        ?string $note = null,
        ?int $storeId = null
    ): StockHistory {
        $resolvedStoreId = $this->resolveStoreId($storeId);

        if (! $resolvedStoreId) {
            $stockBefore = $product->stock;
            $stockAfter = $stockBefore + $quantity;

            $product->update(['stock' => $stockAfter]);

            return $this->createHistory(
                $product,
                $type,
                $quantity,
                $stockBefore,
                $stockAfter,
                $reference,
                $note
            );
        }

        return DB::transaction(function () use ($product, $quantity, $type, $reference, $note, $resolvedStoreId): StockHistory {
            $productStock = $this->getOrCreateProductStock($product, $resolvedStoreId);
            $stockBefore = (int) round((float) $productStock->quantity);
            $stockAfter = $stockBefore + $quantity;

            $productStock->update(['quantity' => $stockAfter]);
            $product->update(['stock' => $stockAfter]);

            return $this->createHistory(
                $product,
                $type,
                $quantity,
                $stockBefore,
                $stockAfter,
                $reference,
                $note
            );
        });
    }

    public function subtractStock(
        Product $product,
        int $quantity,
        StockMovementType $type = StockMovementType::Out,
        ?object $reference = null,
        ?string $note = null,
        ?int $storeId = null
    ): StockHistory {
        $resolvedStoreId = $this->resolveStoreId($storeId);

        if (! $resolvedStoreId) {
            $stockBefore = $product->stock;
            $stockAfter = $stockBefore - $quantity;

            $product->update(['stock' => $stockAfter]);

            return $this->createHistory(
                $product,
                $type,
                -$quantity,
                $stockBefore,
                $stockAfter,
                $reference,
                $note
            );
        }

        return DB::transaction(function () use ($product, $quantity, $type, $reference, $note, $resolvedStoreId): StockHistory {
            $productStock = $this->getOrCreateProductStock($product, $resolvedStoreId);
            $stockBefore = (int) round((float) $productStock->quantity);
            $stockAfter = $stockBefore - $quantity;

            $productStock->update(['quantity' => $stockAfter]);
            $product->update(['stock' => $stockAfter]);

            return $this->createHistory(
                $product,
                $type,
                -$quantity,
                $stockBefore,
                $stockAfter,
                $reference,
                $note
            );
        });
    }

    public function setStock(
        Product $product,
        int $newStock,
        StockMovementType $type = StockMovementType::Opname,
        ?object $reference = null,
        ?string $note = null,
        ?int $storeId = null
    ): StockHistory {
        $resolvedStoreId = $this->resolveStoreId($storeId);

        if (! $resolvedStoreId) {
            $stockBefore = $product->stock;
            $difference = $newStock - $stockBefore;

            $product->update(['stock' => $newStock]);

            return $this->createHistory(
                $product,
                $type,
                $difference,
                $stockBefore,
                $newStock,
                $reference,
                $note
            );
        }

        return DB::transaction(function () use ($product, $newStock, $type, $reference, $note, $resolvedStoreId): StockHistory {
            $productStock = $this->getOrCreateProductStock($product, $resolvedStoreId);
            $stockBefore = (int) round((float) $productStock->quantity);
            $difference = $newStock - $stockBefore;

            $productStock->update(['quantity' => $newStock]);
            $product->update(['stock' => $newStock]);

            return $this->createHistory(
                $product,
                $type,
                $difference,
                $stockBefore,
                $newStock,
                $reference,
                $note
            );
        });
    }

    public function adjustStock(
        Product $product,
        int $quantity,
        bool $isIncrease,
        ?object $reference = null,
        ?string $note = null,
        ?int $storeId = null
    ): StockHistory {
        if ($isIncrease) {
            return $this->addStock($product, $quantity, StockMovementType::Adjustment, $reference, $note, $storeId);
        }

        return $this->subtractStock($product, $quantity, StockMovementType::Adjustment, $reference, $note, $storeId);
    }

    public function isLowStock(Product $product, ?int $storeId = null): bool
    {
        $resolvedStoreId = $this->resolveStoreId($storeId);

        if (! $resolvedStoreId) {
            return $product->stock <= $product->low_stock_threshold;
        }

        $stockQuantity = $product->stockForStore($resolvedStoreId)?->quantity ?? 0;

        return (int) round((float) $stockQuantity) <= $product->low_stock_threshold;
    }

    public function getLowStockProducts(?int $storeId = null): Collection
    {
        $resolvedStoreId = $this->resolveStoreId($storeId);

        if (! $resolvedStoreId) {
            return Product::whereColumn('stock', '<=', 'low_stock_threshold')
                ->where('is_active', true)
                ->orderBy('stock')
                ->get();
        }

        return Product::query()
            ->where('is_active', true)
            ->whereHas('stocks', function ($query) use ($resolvedStoreId) {
                $query->where('store_id', $resolvedStoreId)
                    ->whereColumn('quantity', '<=', 'products.low_stock_threshold');
            })
            ->orderBy(
                ProductStock::query()
                    ->select('quantity')
                    ->whereColumn('product_id', 'products.id')
                    ->where('store_id', $resolvedStoreId)
                    ->limit(1)
            )
            ->get();
    }

    protected function resolveStoreId(?int $storeId): ?int
    {
        return $storeId ?? $this->currentStoreService->getId();
    }

    protected function getOrCreateProductStock(Product $product, int $storeId): ProductStock
    {
        $productStock = ProductStock::query()
            ->where('product_id', $product->id)
            ->where('store_id', $storeId)
            ->whereNull('variant_id')
            ->lockForUpdate()
            ->first();

        if ($productStock) {
            return $productStock;
        }

        return ProductStock::query()->create([
            'product_id' => $product->id,
            'store_id' => $storeId,
            'variant_id' => null,
            'quantity' => $product->stock,
            'min_stock' => $product->low_stock_threshold,
        ]);
    }

    protected function createHistory(
        Product $product,
        StockMovementType $type,
        int $quantity,
        int $stockBefore,
        int $stockAfter,
        ?object $reference = null,
        ?string $note = null
    ): StockHistory {
        return StockHistory::create([
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
            'note' => $note,
            'user_id' => Auth::id(),
        ]);
    }
}
