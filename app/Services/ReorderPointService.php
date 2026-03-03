<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReorderAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReorderPointService
{
    public static function checkStockLevels(): array
    {
        $alertsCreated = [];

        $lowStockProducts = Product::whereColumn('stock', '<=', 'reorder_point')
            ->where(function ($query) {
                $query->whereNull('last_reorder_alert_at')
                    ->orWhere('last_reorder_alert_at', '<=', now()->subDays(1));
            })
            ->get();

        foreach ($lowStockProducts as $product) {
            if (! self::hasPendingAlert($product->id, null)) {
                $alert = self::createAlert($product->id, null, $product->stock, $product->reorder_point);
                $alertsCreated[] = $alert;

                $product->update(['last_reorder_alert_at' => now()]);
            }
        }

        $lowStockVariants = ProductVariant::whereColumn('stock', '<=', 'low_stock_threshold')
            ->where('is_active', true)
            ->get();

        foreach ($lowStockVariants as $variant) {
            if (! self::hasPendingAlert($variant->product_id, $variant->id)) {
                $alert = self::createAlert(
                    $variant->product_id,
                    $variant->id,
                    $variant->stock,
                    $variant->low_stock_threshold
                );
                $alertsCreated[] = $alert;
            }
        }

        return $alertsCreated;
    }

    public static function createAlert(int $productId, ?int $variantId, int $currentStock, int $reorderPoint): ReorderAlert
    {
        return ReorderAlert::create([
            'product_id' => $productId,
            'variant_id' => $variantId,
            'current_stock' => $currentStock,
            'reorder_point' => $reorderPoint,
            'status' => 'pending',
            'notified_at' => now(),
        ]);
    }

    public static function hasPendingAlert(int $productId, ?int $variantId): bool
    {
        return ReorderAlert::where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('status', 'pending')
            ->exists();
    }

    public static function getPendingAlerts(): Collection
    {
        return ReorderAlert::with(['product', 'variant'])
            ->pending()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public static function getAllAlerts(?string $status = null): Collection
    {
        $query = ReorderAlert::with(['product', 'variant', 'acknowledgedBy']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public static function acknowledgeAlert(int $alertId, int $userId, ?string $notes = null): ?ReorderAlert
    {
        $alert = ReorderAlert::find($alertId);

        if (! $alert || ! $alert->isPending()) {
            return null;
        }

        $alert->update([
            'status' => 'acknowledged',
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
            'notes' => $notes,
        ]);

        return $alert->fresh();
    }

    public static function markAsOrdered(int $alertId, ?string $notes = null): ?ReorderAlert
    {
        $alert = ReorderAlert::find($alertId);

        if (! $alert) {
            return null;
        }

        $alert->update([
            'status' => 'ordered',
            'notes' => $notes,
        ]);

        return $alert->fresh();
    }

    public static function dismissAlert(int $alertId): bool
    {
        $alert = ReorderAlert::find($alertId);

        if (! $alert) {
            return false;
        }

        return $alert->delete();
    }

    public static function getAlertsCount(): array
    {
        return [
            'pending' => ReorderAlert::pending()->count(),
            'acknowledged' => ReorderAlert::acknowledged()->count(),
            'ordered' => ReorderAlert::ordered()->count(),
            'total' => ReorderAlert::count(),
        ];
    }

    public static function updateReorderPoint(int $productId, int $reorderPoint, ?int $reorderQuantity = null): ?Product
    {
        $product = Product::find($productId);

        if (! $product) {
            return null;
        }

        $updateData = ['reorder_point' => $reorderPoint];
        if ($reorderQuantity !== null) {
            $updateData['reorder_quantity'] = $reorderQuantity;
        }

        $product->update($updateData);

        return $product->fresh();
    }

    public static function getReorderSummary(): array
    {
        $pendingCount = ReorderAlert::pending()->count();
        $productsNeedReorder = Product::whereColumn('stock', '<=', 'reorder_point')->count();
        $variantsNeedReorder = ProductVariant::whereColumn('stock', '<=', 'low_stock_threshold')
            ->where('is_active', true)
            ->count();

        $totalStockValue = Product::sum(DB::raw('stock * purchase_price'));
        $variantStockValue = ProductVariant::sum(DB::raw('stock * purchase_price'));

        return [
            'pending_alerts' => $pendingCount,
            'products_need_reorder' => $productsNeedReorder,
            'variants_need_reorder' => $variantsNeedReorder,
            'total_items_need_reorder' => $productsNeedReorder + $variantsNeedReorder,
            'total_stock_value' => $totalStockValue + $variantStockValue,
        ];
    }

    public static function getRecommendedPurchaseQuantity(int $productId, ?int $variantId = null): int
    {
        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            if (! $variant) {
                return 0;
            }

            return max($variant->low_stock_threshold * 2, 50);
        }

        $product = Product::find($productId);
        if (! $product) {
            return 0;
        }

        return $product->reorder_quantity ?? max($product->reorder_point * 2, 50);
    }

    public static function shouldNotify(): bool
    {
        return ReorderAlert::pending()->exists();
    }
}
