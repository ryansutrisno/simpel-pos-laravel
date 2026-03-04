<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class GenerateProductImportTemplate extends Command
{
    protected $signature = 'app:generate-product-import-template {--path= : Custom path to save the template}';

    protected $description = 'Generate product import Excel template';

    public function handle(): int
    {
        $templatePath = $this->option('path') ?? storage_path('app/templates/product_import_template.xlsx');

        $directory = dirname($templatePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $template = new class implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithTitle
        {
            public function array(): array
            {
                return [
                    [
                        'nama_produk',
                        'sku',
                        'barcode',
                        'kategori',
                        'harga_beli',
                        'harga_jual',
                        'stok',
                        'reorder_point',
                        'deskripsi',
                        'is_active',
                    ],
                    [
                        'Contoh Produk',
                        'SKU-20260304-0001',
                        '1234567890123',
                        'Makanan',
                        5000,
                        7500,
                        100,
                        10,
                        'Deskripsi produk contoh',
                        true,
                    ],
                ];
            }

            public function title(): string
            {
                return 'Import Produk';
            }
        };

        Excel::store($template, 'templates/product_import_template.xlsx');

        $this->info("Template berhasil dibuat: {$templatePath}");

        return self::SUCCESS;
    }
}
