<?php

namespace App\Services;

use App\Models\BundleItem;
use App\Models\ProductBundle;
use Illuminate\Support\Collection;

class BundleService
{
    public static function getActiveBundles(): Collection
    {
        return ProductBundle::active()
            ->valid()
            ->orderBy('priority', 'desc')
            ->get();
    }

    public static function checkApplicableBundles(array $cartItems): array
    {
        $applicableBundles = [];
        $bundles = self::getActiveBundles();

        foreach ($bundles as $bundle) {
            $result = self::calculateBundleDiscount($bundle->id, $cartItems);
            if ($result['applicable']) {
                $applicableBundles[] = [
                    'bundle' => $bundle,
                    'total_discount' => $result['total_discount'],
                    'free_items' => $result['free_items'],
                    'affected_items' => $result['affected_items'],
                ];
            }
        }

        usort($applicableBundles, function ($a, $b) {
            return $b['total_discount'] <=> $a['total_discount'];
        });

        return $applicableBundles;
    }

    public static function getBestBundle(array $cartItems): ?array
    {
        $applicableBundles = self::checkApplicableBundles($cartItems);

        return $applicableBundles[0] ?? null;
    }

    public static function applyBestBundle(array $cartItems): array
    {
        $bestBundle = self::getBestBundle($cartItems);

        if (! $bestBundle) {
            return [
                'applied' => false,
                'cart_items' => $cartItems,
                'discount' => 0,
            ];
        }

        $bundle = $bestBundle['bundle'];
        $result = self::calculateBundleDiscount($bundle->id, $cartItems);

        $updatedCart = $cartItems;
        $totalDiscount = 0;

        foreach ($result['affected_items'] as $itemDiscount) {
            foreach ($updatedCart as &$cartItem) {
                if ($cartItem['product_id'] == $itemDiscount['product_id'] &&
                    $cartItem['variant_id'] == $itemDiscount['variant_id']) {
                    $cartItem['bundle_discount'] = $itemDiscount['discount'];
                    $cartItem['bundle_id'] = $bundle->id;
                    $cartItem['final_price'] = $cartItem['selling_price'] - $itemDiscount['discount'];
                    $totalDiscount += $itemDiscount['discount'] * $cartItem['quantity'];
                }
            }
        }

        return [
            'applied' => true,
            'bundle' => $bundle,
            'cart_items' => $updatedCart,
            'discount' => $totalDiscount,
            'free_items' => $result['free_items'],
        ];
    }

    public static function calculateBundleDiscount(int $bundleId, array $cartItems): array
    {
        $bundle = ProductBundle::with('items')->find($bundleId);

        if (! $bundle || ! $bundle->isValid()) {
            return [
                'applicable' => false,
                'total_discount' => 0,
                'free_items' => [],
                'affected_items' => [],
            ];
        }

        $bundleItems = $bundle->items;
        $applicable = false;
        $totalDiscount = 0;
        $freeItems = [];
        $affectedItems = [];

        $matchedQuantities = [];
        foreach ($bundleItems as $bundleItem) {
            $matchedQty = 0;
            foreach ($cartItems as $cartItem) {
                if ($cartItem['product_id'] == $bundleItem->product_id &&
                    ($bundleItem->variant_id === null || $cartItem['variant_id'] == $bundleItem->variant_id)) {
                    $matchedQty += $cartItem['quantity'];
                }
            }
            $matchedQuantities[$bundleItem->id] = $matchedQty;
        }

        $minRequired = $bundle->min_quantity;
        $totalMatched = array_sum($matchedQuantities);

        if ($totalMatched >= $minRequired) {
            $applicable = true;

            if ($bundle->isFreeItem()) {
                foreach ($bundleItems as $bundleItem) {
                    if ($bundleItem->is_free) {
                        $freeQty = floor($totalMatched / $minRequired) * $bundleItem->quantity;
                        $freeItems[] = [
                            'product_id' => $bundleItem->product_id,
                            'variant_id' => $bundleItem->variant_id,
                            'quantity' => $freeQty,
                            'name' => $bundleItem->product->name,
                        ];
                    }
                }
            } elseif ($bundle->isPercentage()) {
                foreach ($cartItems as $cartItem) {
                    foreach ($bundleItems as $bundleItem) {
                        if ($cartItem['product_id'] == $bundleItem->product_id) {
                            $discountPerUnit = $cartItem['selling_price'] * ($bundle->discount_value / 100);
                            $affectedItems[] = [
                                'product_id' => $cartItem['product_id'],
                                'variant_id' => $cartItem['variant_id'],
                                'discount' => $discountPerUnit,
                            ];
                            $totalDiscount += $discountPerUnit * $cartItem['quantity'];
                        }
                    }
                }
            } elseif ($bundle->isFixedAmount()) {
                foreach ($cartItems as $cartItem) {
                    foreach ($bundleItems as $bundleItem) {
                        if ($cartItem['product_id'] == $bundleItem->product_id) {
                            $discountPerUnit = min($bundle->discount_value, $cartItem['selling_price']);
                            $affectedItems[] = [
                                'product_id' => $cartItem['product_id'],
                                'variant_id' => $cartItem['variant_id'],
                                'discount' => $discountPerUnit,
                            ];
                            $totalDiscount += $discountPerUnit * $cartItem['quantity'];
                        }
                    }
                }
            }
        }

        return [
            'applicable' => $applicable,
            'total_discount' => $totalDiscount,
            'free_items' => $freeItems,
            'affected_items' => $affectedItems,
        ];
    }

    public static function createBundle(array $data, array $items): ProductBundle
    {
        $bundle = ProductBundle::create($data);

        foreach ($items as $item) {
            BundleItem::create([
                'bundle_id' => $bundle->id,
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $item['quantity'],
                'is_free' => $item['is_free'] ?? false,
            ]);
        }

        return $bundle;
    }

    public static function updateBundle(int $bundleId, array $data, ?array $items = null): ?ProductBundle
    {
        $bundle = ProductBundle::find($bundleId);

        if (! $bundle) {
            return null;
        }

        $bundle->update($data);

        if ($items !== null) {
            BundleItem::where('bundle_id', $bundleId)->delete();

            foreach ($items as $item) {
                BundleItem::create([
                    'bundle_id' => $bundle->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'is_free' => $item['is_free'] ?? false,
                ]);
            }
        }

        return $bundle->fresh();
    }
}
