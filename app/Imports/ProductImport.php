<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class ProductImport implements ToModel, WithBatchInserts, WithHeadingRow, WithValidation
{
    private int $batchSize = 100;

    private static int $skuCounter = 0;

    private static string $lastSkuDate = '';

    private array $errors = [];

    private ?object $callback = null;

    private int $totalRows = 0;

    private int $processedRows = 0;

    public function __construct(?object $callback = null)
    {
        $this->callback = $callback;
    }

    public function setTotalRows(int $total): void
    {
        $this->totalRows = $total;
    }

    public function model(array $row): ?Product
    {
        $this->processedRows++;

        if ($this->callback && method_exists($this->callback, 'updateProgress')) {
            $this->callback->updateProgress($this->processedRows, $this->totalRows);
        }

        $validated = $this->validateRow($row);

        $category = null;
        if (! empty($row['kategori'])) {
            $category = Category::firstOrCreate(['name' => trim($row['kategori'])]);
        }

        $sku = $this->generateSku(trim($row['sku'] ?? ''));

        return new Product([
            'name' => trim($row['nama_produk']),
            'sku' => $sku,
            'category_id' => $category?->id,
            'description' => $row['deskripsi'] ?? null,
            'purchase_price' => (float) $row['harga_beli'],
            'selling_price' => (float) $row['harga_jual'],
            'stock' => (int) $row['stok'],
            'reorder_point' => ! empty($row['reorder_point']) ? (int) $row['reorder_point'] : null,
            'barcode' => ! empty($row['barcode']) ? trim($row['barcode']) : null,
            'is_active' => isset($row['is_active']) ? $this->parseBoolean($row['is_active']) : true,
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_produk' => ['required', 'string', 'min:3'],
            'harga_beli' => ['required', 'numeric', 'min:0'],
            'harga_jual' => ['required', 'numeric', 'min:0'],
            'stok' => ['required', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'unique:products,barcode'],
            'kategori' => ['nullable', 'string'],
            'reorder_point' => ['nullable', 'integer', 'min:0'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();

            foreach ($data as $rowIndex => $row) {
                $hargaBeli = (float) ($row['harga_beli'] ?? 0);
                $hargaJual = (float) ($row['harga_jual'] ?? 0);

                if ($hargaJual < $hargaBeli) {
                    $validator->errors()->add(
                        $rowIndex.'.harga_jual',
                        'Harga jual harus lebih besar atau sama dengan harga beli'
                    );
                }
            }
        });
    }

    public function batchSize(): int
    {
        return $this->batchSize;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    private function validateRow(array $row): array
    {
        $validator = Validator::make($row, $this->rules());

        if ($validator->fails()) {
            $messages = [];
            foreach ($validator->errors()->messages() as $field => $errors) {
                $messages[] = "{$field}: ".implode(', ', $errors);
            }
            throw new \Exception('Validasi gagal: '.implode('; ', $messages));
        }

        return $validator->validated();
    }

    private function generateSku(?string $providedSku): string
    {
        if (! empty($providedSku)) {
            return trim($providedSku);
        }

        $date = now()->format('Ymd');

        // Reset counter if it's a new day
        if (self::$lastSkuDate !== $date) {
            self::$skuCounter = 0;
            self::$lastSkuDate = $date;
        }

        // Find the last SKU from database for the current date
        $prefix = "SKU-{$date}-";
        $lastProduct = Product::where('sku', 'like', $prefix.'%')
            ->orderByDesc('sku')
            ->first();

        $baseNumber = 1;
        if ($lastProduct) {
            $lastNumber = (int) str_replace($prefix, '', $lastProduct->sku);
            $baseNumber = max($lastNumber + 1, self::$skuCounter + 1);
        }

        // Use the higher of database counter or in-memory counter
        $nextNumber = max($baseNumber, self::$skuCounter + 1);
        self::$skuCounter = $nextNumber;

        return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    private function parseBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            $lower = strtolower(trim($value));

            return in_array($lower, ['true', '1', 'yes', 'ya', 'aktif', 'active'], true);
        }

        return true;
    }

    public function failed(\Illuminate\Validation\ValidationException|array $failures): void
    {
        $errorMessages = [];

        foreach ($failures as $failure) {
            if ($failure instanceof Failure) {
                $row = $failure->row();
                foreach ($failure->errors() as $error) {
                    $errorMessages[] = "Baris {$row}: {$error}";
                }
            } elseif (is_array($failure)) {
                foreach ($failure as $rowNumber => $errors) {
                    foreach ($errors as $field => $error) {
                        $errorMessages[] = "Baris {$rowNumber}: {$error}";
                    }
                }
            }
        }

        if (! empty($errorMessages)) {
            throw new \Exception(implode("\n", $errorMessages));
        }
    }
}
