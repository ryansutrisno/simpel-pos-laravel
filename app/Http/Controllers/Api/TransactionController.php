<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\ReceiptTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    protected ReceiptTemplateService $templateService;

    public function __construct(ReceiptTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    public function show(Transaction $transaction): JsonResponse
    {
        $this->authorize('view', $transaction);

        $transaction->load('items.product', 'user');
        $store = \App\Models\Store::first();

        // Get the active receipt template for this store
        $activeTemplate = $this->templateService->getActiveTemplate($store);

        $data = [
            'id' => $transaction->id,
            'date' => $transaction->created_at->format('d/m/Y H:i'),
            'cashier' => $transaction->user->name,
            'store' => [
                'name' => $store->name ?? config('app.name', 'POS Store'),
                'address' => $store->address ?? '',
                'phone' => $store->phone ?? '',
                'tagline' => $store->receipt_tagline ?? '',
                'logo_url' => $store->logo_url,
            ],
            'items' => $transaction->items->map(function ($item) {
                return [
                    'name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $item->selling_price,
                    'subtotal' => $item->subtotal,
                ];
            }),
            'payment' => [
                'total' => $transaction->total,
                'method' => $transaction->payment_method,
                'cash_amount' => $transaction->cash_amount,
                'change_amount' => $transaction->change_amount,
            ],
            'template' => $activeTemplate ? [
                'id' => $activeTemplate->id,
                'name' => $activeTemplate->name,
                'template_data' => $activeTemplate->template_data,
            ] : null,
        ];

        return response()->json([
            'transaction' => $data,
        ]);
    }

    /**
     * Get receipt preview for a transaction.
     */
    public function preview(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('view', $transaction);

        $transaction->load('items.product', 'user');
        $store = \App\Models\Store::first();

        // Get the template from request or use active template
        $templateId = $request->input('template_id');
        $template = null;

        if ($templateId) {
            $template = \App\Models\ReceiptTemplate::find($templateId);
        } else {
            $template = $this->templateService->getActiveTemplate($store);
        }

        if (! $template) {
            return response()->json([
                'error' => 'No receipt template found',
            ], 404);
        }

        // Prepare data for preview
        $data = [
            'transaction' => [
                'id' => $transaction->id,
                'date' => $transaction->created_at->format('d/m/Y H:i'),
                'cashier' => $transaction->user->name,
            ],
            'store' => [
                'name' => $store->name ?? config('app.name', 'POS Store'),
                'address' => $store->address ?? '',
                'phone' => $store->phone ?? '',
                'tagline' => $store->receipt_tagline ?? '',
                'logo_url' => $store->logo_url,
            ],
            'items' => $transaction->items->map(function ($item) {
                return [
                    'name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $item->selling_price,
                    'subtotal' => $item->subtotal,
                ];
            }),
            'payment' => [
                'total' => $transaction->total,
                'method' => $transaction->payment_method,
                'cash_received' => $transaction->cash_amount,
                'change_amount' => $transaction->change_amount,
            ],
        ];

        $preview = $this->templateService->getTemplatePreview($template, $data);

        return response()->json($preview);
    }

    /**
     * Get available receipt templates.
     */
    public function templates(): JsonResponse
    {
        $store = \App\Models\Store::first();
        $templates = $this->templateService->getAvailableTemplates($store);

        return response()->json([
            'templates' => $templates->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'is_default' => $template->is_default,
                    'is_active' => $template->is_active,
                    'template_data' => $template->template_data,
                ];
            }),
        ]);
    }
}
