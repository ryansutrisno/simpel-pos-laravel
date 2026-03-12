<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Transaction;

class TransactionService
{
    public function __construct(
        protected CurrentStoreService $currentStoreService,
        protected VariantService $variantService,
    ) {}

    public function create(array $attributes): Transaction
    {
        $attributes['store_id'] = $attributes['store_id'] ?? $this->currentStoreService->getId();

        return Transaction::create($attributes);
    }

    public function deductStockForItem(array $item, ?int $storeId = null): void
    {
        $resolvedStoreId = $storeId ?? $this->currentStoreService->getId();

        if ($resolvedStoreId) {
            $stockQuery = ProductStock::query()
                ->where('store_id', $resolvedStoreId)
                ->where('product_id', $item['product_id']);

            if (! empty($item['variant_id'])) {
                $stockQuery->where('variant_id', $item['variant_id']);
            } else {
                $stockQuery->whereNull('variant_id');
            }

            $productStock = $stockQuery->first();

            if ($productStock) {
                $productStock->decrement('quantity', $item['quantity']);

                return;
            }
        }

        if (! empty($item['variant_id'])) {
            $this->variantService->decrementStock((int) $item['variant_id'], (int) $item['quantity']);

            return;
        }

        Product::query()->whereKey($item['product_id'])->decrement('stock', (int) $item['quantity']);
    }
}
