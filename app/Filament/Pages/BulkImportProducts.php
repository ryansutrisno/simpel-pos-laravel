<?php

namespace App\Filament\Pages;

use App\Imports\ProductImport;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BulkImportProducts extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';

    protected static ?string $navigationGroup = 'Manajemen Produk';

    protected static ?string $navigationLabel = 'Import Produk';

    protected static ?string $title = 'Import Produk Massal';

    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.pages.bulk-import-products';

    public ?UploadedFile $file = null;

    public int $progress = 0;

    public bool $isImporting = false;

    public string $statusMessage = '';

    public function updatedFile(): void
    {
        $this->reset('progress', 'statusMessage');
    }

    public function downloadTemplate(): void
    {
        $templatePath = storage_path('app/public/templates/product_import_template.xlsx');

        if (! file_exists($templatePath)) {
            Notification::make()
                ->title('Template Tidak Ditemukan')
                ->body('File template tidak ditemukan. Silakan hubungi administrator.')
                ->danger()
                ->send();

            return;
        }

        response()->download($templatePath, 'template_import_produk.xlsx')->send();
    }

    public function import(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx'],
        ]);

        $this->isImporting = true;
        $this->progress = 0;
        $this->statusMessage = 'Memproses file...';

        try {
            // Use Livewire's temporary file path directly
            $realPath = $this->file->getRealPath();
            $originalName = $this->file->getClientOriginalName();

            Log::info('Using file: '.$originalName.' from temp path: '.$realPath);

            $totalRows = $this->getTotalRows($realPath);

            Log::info('Total rows detected: '.$totalRows);

            if ($totalRows === 0) {
                $this->isImporting = false;
                Notification::make()
                    ->title('Import Gagal')
                    ->body('File tidak mengandung data.')
                    ->danger()
                    ->send();

                return;
            }

            $this->statusMessage = "Mengimpor {$totalRows} produk...";

            $import = new ProductImport($this);
            $import->setTotalRows($totalRows);

            \Maatwebsite\Excel\Facades\Excel::import($import, $realPath);

            $importedCount = $totalRows;

            $this->isImporting = false;
            $this->progress = 100;

            Notification::make()
                ->title('Import Berhasil')
                ->body("{$importedCount} produk berhasil diimpor.")
                ->success()
                ->send();

            $this->statusMessage = "Import berhasil! {$importedCount} produk berhasil diimpor.";
            $this->file = null;

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $this->isImporting = false;

            $errors = $e->failures();
            $errorMessages = [];

            foreach ($errors as $failure) {
                $row = $failure->row();
                $field = $failure->attribute();
                $errorsList = $failure->errors();

                foreach ($errorsList as $error) {
                    $fieldLabel = $this->getFieldLabel($field);
                    $errorMessages[] = "Baris {$row}: Kolom '{$fieldLabel}' {$error}";
                }
            }

            $errorText = implode("\n", $errorMessages);
            $this->statusMessage = "Import gagal: {$errorText}";

            Notification::make()
                ->title('Import Gagal')
                ->body($errorText)
                ->danger()
                ->send();

        } catch (\Exception $e) {
            $this->isImporting = false;

            $errorMessage = $e->getMessage();
            $this->statusMessage = "Import gagal: {$errorMessage}";

            Log::error('Bulk Import Error: '.$errorMessage);

            Notification::make()
                ->title('Import Gagal')
                ->body($errorMessage)
                ->danger()
                ->send();
        }
    }

    private function getTotalRows(string $filePath): int
    {
        try {

            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            return max(0, $highestRow - 1);
        } catch (\Exception $e) {
            Log::error('Error reading Excel rows: '.$e->getMessage());

            return 0;
        }
    }

    private function getFieldLabel(string $field): string
    {
        $labels = [
            'nama_produk' => 'nama_produk',
            'harga_beli' => 'harga_beli',
            'harga_jual' => 'harga_jual',
            'stok' => 'stok',
            'sku' => 'sku',
            'barcode' => 'barcode',
            'kategori' => 'kategori',
            'reorder_point' => 'reorder_point',
            'deskripsi' => 'deskripsi',
            'is_active' => 'is_active',
        ];

        return $labels[$field] ?? $field;
    }

    private function cleanupFile(?string $filePath): void
    {
        if ($filePath && file_exists($filePath)) {
            try {
                unlink($filePath);
            } catch (\Exception $e) {
                Log::warning('Failed to cleanup temp file: '.$e->getMessage());
            }
        }
    }

    public function updateProgress(int $current, int $total): void
    {
        if ($total > 0) {
            $this->progress = (int) (($current / $total) * 100);
        }
    }
}
