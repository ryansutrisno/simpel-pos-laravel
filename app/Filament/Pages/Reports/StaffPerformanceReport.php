<?php

namespace App\Filament\Pages\Reports;

use App\Models\User;
use App\Services\StaffPerformanceService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class StaffPerformanceReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Performa Staff';

    protected static ?string $title = 'Laporan Performa Staff';

    protected static string $view = 'filament.pages.reports.staff-performance';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => today()->startOfMonth()->format('Y-m-d'),
            'end_date' => today()->format('Y-m-d'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make([
                    'default' => 1,
                    'sm' => 2,
                    'lg' => 3,
                ])
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Dari Tanggal')
                            ->required()
                            ->default(today()->startOfMonth()),

                        DatePicker::make('end_date')
                            ->label('Sampai Tanggal')
                            ->required()
                            ->default(today()),

                        Select::make('user_id')
                            ->label('Kasir')
                            ->options(User::pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Semua Kasir')
                            ->preload(),
                    ]),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $startDate = $this->data['start_date'] ?? today()->startOfMonth();
                $endDate = $this->data['end_date'] ?? today();
                $userId = $this->data['user_id'] ?? null;

                return User::query()
                    ->when($userId, fn ($q) => $q->where('id', $userId))
                    ->whereHas('transactions', function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('created_at', [
                            \Carbon\Carbon::parse($startDate)->startOfDay(),
                            \Carbon\Carbon::parse($endDate)->endOfDay(),
                        ]);
                    });
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Kasir')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_sales')
                    ->label('Total Penjualan')
                    ->money('IDR')
                    ->getStateUsing(function ($record) {
                        $startDate = $this->data['start_date'] ?? today();
                        $endDate = $this->data['end_date'] ?? today();

                        return StaffPerformanceService::getSalesByUser(
                            $record->id,
                            \Carbon\Carbon::parse($startDate),
                            \Carbon\Carbon::parse($endDate)
                        );
                    }),

                TextColumn::make('transaction_count')
                    ->label('Jumlah Transaksi')
                    ->getStateUsing(function ($record) {
                        $startDate = $this->data['start_date'] ?? today();
                        $endDate = $this->data['end_date'] ?? today();

                        return StaffPerformanceService::getTransactionCountByUser(
                            $record->id,
                            \Carbon\Carbon::parse($startDate),
                            \Carbon\Carbon::parse($endDate)
                        );
                    }),

                TextColumn::make('average_transaction')
                    ->label('Rata-rata Transaksi')
                    ->money('IDR')
                    ->getStateUsing(function ($record) {
                        $startDate = $this->data['start_date'] ?? today();
                        $endDate = $this->data['end_date'] ?? today();

                        return StaffPerformanceService::getAverageTransactionValue(
                            $record->id,
                            \Carbon\Carbon::parse($startDate),
                            \Carbon\Carbon::parse($endDate)
                        );
                    }),

                TextColumn::make('items_sold')
                    ->label('Item Terjual')
                    ->getStateUsing(function ($record) {
                        $startDate = $this->data['start_date'] ?? today();
                        $endDate = $this->data['end_date'] ?? today();

                        return StaffPerformanceService::getItemsSoldByUser(
                            $record->id,
                            \Carbon\Carbon::parse($startDate),
                            \Carbon\Carbon::parse($endDate)
                        );
                    }),
            ])
            ->defaultSort('name', 'asc')
            ->paginated(false);
    }

    public function getTopStaffProperty()
    {
        $date = today();

        return StaffPerformanceService::getTopStaff(5, $date);
    }
}
