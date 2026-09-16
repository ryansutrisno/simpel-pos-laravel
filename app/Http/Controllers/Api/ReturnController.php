<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductReturn;
use App\Models\ReceiptTemplate;
use App\Models\Store;
use App\Services\ReceiptTemplateService;
use Illuminate\Http\JsonResponse;

class ReturnController extends Controller
{
    public function show(int $id): JsonResponse
    {
        $return = ProductReturn::with([
            'items.product',
            'items.exchangeProduct',
            'transaction',
            'customer',
            'user',
        ])->findOrFail($id);

        $this->authorize('view', $return);

        return response()->json([
            'success' => true,
            'data' => $return,
        ]);
    }

    public function receipt(int $id, ReceiptTemplateService $templateService): JsonResponse
    {
        $return = ProductReturn::with([
            'items.product',
            'items.exchangeProduct',
            'items.transactionItem',
            'transaction',
            'customer',
            'user',
        ])->findOrFail($id);

        $this->authorize('view', $return);

        $template = ReceiptTemplate::where('name', 'Return Receipt')
            ->where('is_active', true)
            ->first();

        if (! $template) {
            $store = Store::first();
            $template = $templateService->getActiveTemplate($store);
        }

        $store = Store::first();

        $data = [
            'id' => $return->id,
            'return_number' => $return->return_number,
            'date' => $return->return_date?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i'),
            'cashier' => $return->user->name,
            'transaction_id' => $return->transaction_id,
            'type' => $return->type->value,
            'reason_category' => $return->reason_category?->getLabel(),
            'reason_note' => $return->reason_note,
            'store' => [
                'name' => $store->name ?? config('app.name', 'POS Store'),
                'address' => $store->address ?? '',
                'phone' => $store->phone ?? '',
                'tagline' => $store->receipt_tagline ?? '',
                'logo_url' => $store->logo_url,
            ],
            'items' => $return->items->map(function ($item) {
                $itemData = [
                    'name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                    'is_exchange' => $item->is_exchange,
                ];

                if ($item->hasExchange()) {
                    $itemData['exchange_product_name'] = $item->exchangeProduct->name;
                    $itemData['exchange_quantity'] = $item->exchange_quantity;
                    $itemData['exchange_price'] = $item->exchange_unit_price;
                    $itemData['exchange_subtotal'] = $item->exchange_subtotal;
                }

                return $itemData;
            }),
            'refund' => [
                'method' => $return->refund_method?->value,
                'method_label' => $return->refund_method?->getLabel(),
                'total_refund' => $return->total_refund,
                'total_exchange_value' => $return->total_exchange_value,
                'selisih_amount' => $return->selisih_amount,
                'selisih_type' => $return->getSelisihType(),
            ],
            'notes' => $return->notes,
            'template' => $template ? [
                'id' => $template->id,
                'name' => $template->name,
                'template_data' => $template->template_data,
            ] : null,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'return' => $data,
                'template_data' => $template?->template_data,
                'store' => $store,
            ],
        ]);
    }
}
