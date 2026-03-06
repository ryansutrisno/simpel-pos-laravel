<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class InventoryValuationExport implements FromCollection, WithHeadings, WithTitle
{
    protected array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection(): Collection
    {
        $items = collect($this->reportData['items'])->map(function ($item) {
            return [
                'product' => $item['product']->name,
                'sku' => $item['sku'] ?? '-',
                'category' => $item['category'] ?? '-',
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'total_value' => $item['total_value'],
            ];
        });

        // Add total row
        $items->push([
            'product' => 'TOTAL',
            'sku' => '',
            'category' => '',
            'quantity' => $this->reportData['total_quantity'],
            'unit_cost' => '',
            'total_value' => $this->reportData['total_value'],
        ]);

        return $items;
    }

    public function headings(): array
    {
        return [
            'Produk',
            'SKU',
            'Kategori',
            'Qty',
            'Harga Satuan',
            'Total Nilai',
        ];
    }

    public function title(): string
    {
        return 'Nilai Inventory - '.$this->reportData['method_label'].' - '.$this->reportData['reference_date'];
    }
}
