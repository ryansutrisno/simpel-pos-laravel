<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PurchasePriceHistoryService
{
    public function getPriceHistory(
        ?int $productId = null,
        ?int $supplierId = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $query = PurchaseOrderItem::query()
            ->whereHas('purchaseOrder', function ($q) use ($supplierId, $startDate, $endDate) {
                $q->where('status', 'received');

                if ($supplierId) {
                    $q->where('supplier_id', $supplierId);
                }

                if ($startDate) {
                    $q->whereDate('received_date', '>=', $startDate);
                }

                if ($endDate) {
                    $q->whereDate('received_date', '<=', $endDate);
                }
            })
            ->with(['product', 'purchaseOrder', 'purchaseOrder.supplier'])
            ->orderBy('created_at', 'desc');

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $items = $query->get();

        $prices = $items->pluck('purchase_price')->filter();

        $summary = [
            'min_price' => $prices->min() ?? 0,
            'max_price' => $prices->max() ?? 0,
            'avg_price' => $prices->avg() ?? 0,
            'total_transactions' => $items->count(),
            'total_quantity' => $items->sum('quantity_received'),
        ];

        return [
            'items' => $items,
            'summary' => $summary,
            'start_date' => $startDate?->toDateString(),
            'end_date' => $endDate?->toDateString(),
        ];
    }

    public function getPriceTrend(?int $productId = null, ?int $supplierId = null): array
    {
        $query = PurchaseOrderItem::query()
            ->whereHas('purchaseOrder', function ($q) use ($supplierId) {
                $q->where('status', 'received');

                if ($supplierId) {
                    $q->where('supplier_id', $supplierId);
                }
            })
            ->with(['product', 'purchaseOrder']);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $items = $query->orderBy('created_at', 'asc')->get();

        // Group by month
        $trend = $items->groupBy(function ($item) {
            return $item->purchaseOrder->received_date->format('Y-m');
        })->map(function ($group) {
            return [
                'month' => $group->first()->purchaseOrder->received_date->format('Y-m'),
                'month_label' => $group->first()->purchaseOrder->received_date->format('M Y'),
                'avg_price' => $group->avg('purchase_price'),
                'min_price' => $group->min('purchase_price'),
                'max_price' => $group->max('purchase_price'),
                'count' => $group->count(),
            ];
        })->values();

        return [
            'trend' => $trend,
            'product_id' => $productId,
            'supplier_id' => $supplierId,
        ];
    }

    public function getLatestPrice(?int $productId = null, ?int $supplierId = null): ?float
    {
        $query = PurchaseOrderItem::query()
            ->whereHas('purchaseOrder', function ($q) use ($supplierId) {
                $q->where('status', 'received');

                if ($supplierId) {
                    $q->where('supplier_id', $supplierId);
                }
            })
            ->orderBy('created_at', 'desc');

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $latest = $query->first();

        return $latest ? (float) $latest->purchase_price : null;
    }

    public function getProductsWithHistory(): Collection
    {
        return Product::whereHas('purchaseOrderItems.purchaseOrder', function ($q) {
            $q->where('status', 'received');
        })->orderBy('name')->get();
    }

    public function getSuppliersWithHistory(): Collection
    {
        return Supplier::whereHas('purchaseOrders', function ($q) {
            $q->where('status', 'received');
        })->orderBy('name')->get();
    }
}
