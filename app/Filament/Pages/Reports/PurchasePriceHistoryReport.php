<?php

namespace App\Filament\Pages\Reports;

use App\Services\PurchasePriceHistoryService;
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

class PurchasePriceHistoryReport extends Page implements HasForms, HasTable
{
    use HasPageShield;
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string $view = 'filament.pages.reports.purchase-price-history-report';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Riwayat Harga Beli';

    public ?int $productId = null;

    public ?int $supplierId = null;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public array $reportData = [];

    public array $trendData = [];

    protected PurchasePriceHistoryService $historyService;

    public function boot(PurchasePriceHistoryService $historyService): void
    {
        $this->historyService = $historyService;
    }

    public function mount(): void
    {
        $this->startDate = now()->startOfYear()->toDateString();
        $this->endDate = now()->toDateString();
        $this->generateReport();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Laporan')
                    ->schema([
                        Select::make('productId')
                            ->label('Produk')
                            ->options($this->historyService->getProductsWithHistory()->pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport()),
                        Select::make('supplierId')
                            ->label('Supplier')
                            ->options($this->historyService->getSuppliersWithHistory()->pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport()),
                        DatePicker::make('startDate')
                            ->label('Tanggal Mulai')
                            ->default(now()->startOfYear())
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport()),
                        DatePicker::make('endDate')
                            ->label('Tanggal Akhir')
                            ->default(now())
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport()),
                    ])
                    ->columns(4),
            ]);
    }

    public function generateReport(): void
    {
        $state = $this->form->getState();

        $productId = $state['productId'] ?? $this->productId;
        $supplierId = $state['supplierId'] ?? $this->supplierId;
        $startDate = $state['startDate'] ?? $this->startDate;
        $endDate = $state['endDate'] ?? $this->endDate;

        if (! $startDate || ! $endDate) {
            return;
        }

        $this->reportData = $this->historyService->getPriceHistory(
            $productId,
            $supplierId,
            Carbon::parse($startDate),
            Carbon::parse($endDate)
        );

        $this->trendData = $this->historyService->getPriceTrend($productId, $supplierId);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => \App\Models\PurchaseOrderItem::query()
                ->whereHas('purchaseOrder', function ($q) {
                    $q->where('status', 'received');
                })
                ->when($this->productId, fn ($q) => $q->where('product_id', $this->productId))
                ->when($this->supplierId, fn ($q) => $q->whereHas('purchaseOrder', fn ($q2) => $q2->where('supplier_id', $this->supplierId)))
                ->with(['product', 'purchaseOrder', 'purchaseOrder.supplier']))
            ->columns([
                TextColumn::make('purchaseOrder.received_date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('purchaseOrder.order_number')
                    ->label('No. PO')
                    ->searchable(),
                TextColumn::make('purchaseOrder.supplier.name')
                    ->label('Supplier')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable(),
                TextColumn::make('purchase_price')
                    ->label('Harga Beli')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('quantity_received')
                    ->label('Qty Diterima')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('purchaseOrder.received_date', 'desc');
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->reportData,
            'trendData' => $this->trendData,
        ];
    }
}
