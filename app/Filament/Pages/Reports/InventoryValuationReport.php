<?php

namespace App\Filament\Pages\Reports;

use App\Exports\InventoryValuationExport;
use App\Models\Category;
use App\Services\InventoryValuationService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

class InventoryValuationReport extends Page implements HasForms, HasTable
{
    use HasPageShield;
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string $view = 'filament.pages.reports.inventory-valuation-report';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Nilai Inventory';

    public ?string $method = null;

    public ?string $referenceDate = null;

    public ?int $categoryId = null;

    public array $reportData = [];

    protected InventoryValuationService $valuationService;

    public function boot(InventoryValuationService $valuationService): void
    {
        $this->valuationService = $valuationService;
    }

    public function mount(): void
    {
        $this->method = InventoryValuationService::METHOD_WEIGHTED_AVERAGE;
        $this->referenceDate = now()->toDateString();
        $this->generateReport();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Laporan')
                    ->schema([
                        Select::make('method')
                            ->label('Metode Valuasi')
                            ->options($this->valuationService->getAvailableMethods())
                            ->default(InventoryValuationService::METHOD_WEIGHTED_AVERAGE)
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport()),
                        DatePicker::make('referenceDate')
                            ->label('Tanggal Valuasi')
                            ->default(now())
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport()),
                        Select::make('categoryId')
                            ->label('Kategori')
                            ->options(Category::pluck('name', 'id'))
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport()),
                    ])
                    ->columns(3),
            ]);
    }

    public function generateReport(): void
    {
        $state = $this->form->getState();

        $method = $state['method'] ?? $this->method;
        $referenceDate = $state['referenceDate'] ?? $this->referenceDate;
        $categoryId = $state['categoryId'] ?? $this->categoryId;

        if (! $method || ! $referenceDate) {
            return;
        }

        $this->reportData = $this->valuationService->getInventoryValue(
            $method,
            Carbon::parse($referenceDate),
            $categoryId
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => \App\Models\Product::query()
                ->where('is_active', true)
                ->where('stock', '>', 0)
                ->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId)))
            ->columns([
                TextColumn::make('name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),
                TextColumn::make('stock')
                    ->label('Qty')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('purchase_price')
                    ->label('Harga Beli')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->defaultSort('name');
    }

    public function exportExcel()
    {
        $this->generateReport();

        if (empty($this->reportData)) {
            return;
        }

        return Excel::download(
            new InventoryValuationExport($this->reportData),
            'nilai-inventory-'.$this->referenceDate.'.xlsx'
        );
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->reportData,
        ];
    }
}
