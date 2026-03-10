<?php

namespace App\Services;

use App\Models\PrinterConfig;
use App\Models\ReceiptTemplate;
use App\Models\Store;

class ReceiptTemplateService
{
    /**
     * Get the default printer configuration for a store.
     */
    public function getDefaultPrinter(?Store $store = null): ?PrinterConfig
    {
        $storeId = $store?->id;

        return PrinterConfig::forStore($storeId)
            ->default()
            ->active()
            ->first();
    }

    /**
     * Get all available printers for a store.
     */
    public function getAvailablePrinters(?Store $store = null): \Illuminate\Database\Eloquent\Collection
    {
        $storeId = $store?->id;

        return PrinterConfig::forStore($storeId)
            ->active()
            ->get();
    }

    /**
     * Get the active receipt template for a store.
     */
    public function getActiveTemplate(?Store $store = null): ?ReceiptTemplate
    {
        // First check if store has a specific template set
        if ($store && $store->receipt_template_id) {
            return ReceiptTemplate::where('id', $store->receipt_template_id)
                ->where('is_active', true)
                ->first();
        }

        // Fallback to default template
        return ReceiptTemplate::getDefaultTemplate();
    }

    /**
     * Get all available templates for a store.
     */
    public function getAvailableTemplates(?Store $store = null): \Illuminate\Database\Eloquent\Collection
    {
        return ReceiptTemplate::getActiveTemplates($store?->id);
    }

    /**
     * Create a new template.
     */
    public function createTemplate(array $data, ?Store $store = null): ReceiptTemplate
    {
        // If this is set as default, unset other defaults
        if ($data['is_default'] ?? false) {
            ReceiptTemplate::where('store_id', $store?->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $data['store_id'] = $store?->id;

        return ReceiptTemplate::create($data);
    }

    /**
     * Update an existing template.
     */
    public function updateTemplate(ReceiptTemplate $template, array $data): ReceiptTemplate
    {
        // If this is set as default, unset other defaults
        if ($data['is_default'] ?? false) {
            ReceiptTemplate::where('id', '!=', $template->id)
                ->where('store_id', $template->store_id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $template->update($data);

        return $template;
    }

    /**
     * Delete a template.
     */
    public function deleteTemplate(ReceiptTemplate $template): bool
    {
        return $template->delete();
    }

    /**
     * Validate template data structure.
     */
    public function validateTemplateData(array $templateData): array
    {
        $requiredSections = ['header', 'body', 'footer'];
        $errors = [];

        foreach ($requiredSections as $section) {
            if (! isset($templateData[$section])) {
                $errors[] = "Missing required section: {$section}";
            }
        }

        // Validate header section
        if (isset($templateData['header'])) {
            $header = $templateData['header'];
            if (isset($header['show_logo']) && ! is_bool($header['show_logo'])) {
                $errors[] = "Header 'show_logo' must be boolean";
            }
            if (isset($header['custom_header_message']) && ! is_string($header['custom_header_message'])) {
                $errors[] = "Header 'custom_header_message' must be string";
            }
        }

        // Validate body section
        if (isset($templateData['body'])) {
            $body = $templateData['body'];
            if (isset($body['item_format']) && ! in_array($body['item_format'], ['name_price_quantity', 'name_only', 'price_only'])) {
                $errors[] = "Body 'item_format' must be one of: name_price_quantity, name_only, price_only";
            }
        }

        // Validate footer section
        if (isset($templateData['footer'])) {
            $footer = $templateData['footer'];
            if (isset($footer['custom_footer_message']) && ! is_string($footer['custom_footer_message'])) {
                $errors[] = "Footer 'custom_footer_message' must be string";
            }
            if (isset($footer['show_barcode']) && ! is_bool($footer['show_barcode'])) {
                $errors[] = "Footer 'show_barcode' must be boolean";
            }
        }

        // Validate styling section
        if (isset($templateData['styling'])) {
            $styling = $templateData['styling'];
            if (isset($styling['font_size']) && ! in_array($styling['font_size'], ['small', 'normal', 'large'])) {
                $errors[] = "Styling 'font_size' must be one of: small, normal, large";
            }
            if (isset($styling['text_alignment']) && ! in_array($styling['text_alignment'], ['left', 'center', 'right'])) {
                $errors[] = "Styling 'text_alignment' must be one of: left, center, right";
            }
            if (isset($styling['separator_style']) && ! in_array($styling['separator_style'], ['dashes', 'dots', 'line'])) {
                $errors[] = "Styling 'separator_style' must be one of: dashes, dots, line";
            }
        }

        return $errors;
    }

    /**
     * Get template preview data.
     */
    public function getTemplatePreview(ReceiptTemplate $template, array $sampleData = []): array
    {
        $defaultSampleData = [
            'transaction' => [
                'id' => 'TXN-2026-001',
                'date' => now()->format('d/m/Y H:i'),
                'cashier' => 'John Doe',
            ],
            'store' => [
                'name' => 'Sample Store',
                'address' => '123 Sample Street',
                'phone' => '081234567890',
                'tagline' => 'Your trusted partner',
            ],
            'items' => [
                [
                    'name' => 'Product A',
                    'quantity' => 2,
                    'price' => 25000,
                    'subtotal' => 50000,
                ],
                [
                    'name' => 'Product B',
                    'quantity' => 1,
                    'price' => 15000,
                    'subtotal' => 15000,
                ],
            ],
            'payment' => [
                'method' => 'cash',
                'total' => 65000,
                'cash_received' => 100000,
                'change_amount' => 35000,
            ],
        ];

        $sampleData = array_merge($defaultSampleData, $sampleData);

        return [
            'template' => $template->toArray(),
            'preview_data' => $sampleData,
            'rendered_receipt' => $this->renderReceipt($template, $sampleData),
        ];
    }

    /**
     * Render receipt based on template and data.
     */
    public function renderReceipt(ReceiptTemplate $template, array $data): string
    {
        $renderer = new ReceiptRenderer($template, $data);

        return $renderer->render();
    }
}
