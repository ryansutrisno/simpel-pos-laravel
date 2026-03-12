<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftResource\Pages;
use App\Models\Shift;
use App\Models\Store;
use App\Models\User;
use App\Services\CurrentStoreService;
use App\Services\ShiftService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Manajemen Operasional';

    protected static ?string $navigationLabel = 'Shift Kasir';

    protected static ?string $pluralLabel = 'Shift Kasir';

    protected static ?string $modelLabel = 'Shift';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Shift')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Kasir')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(fn () => Auth::id())
                            ->disabled(fn () => ! (static::currentUser()?->hasRole(['admin', 'super_admin']) ?? false))
                            ->hidden(fn () => ! (static::currentUser()?->hasRole(['admin', 'super_admin']) ?? false))
                            ->disabledOn('edit'),

                        Forms\Components\Placeholder::make('kasir_name')
                            ->label('Kasir')
                            ->content(fn () => static::currentUser()?->name ?? '-')
                            ->hidden(fn () => static::currentUser()?->hasRole(['admin', 'super_admin']) ?? false),

                        Forms\Components\Select::make('store_id')
                            ->label('Toko')
                            ->options(Store::pluck('name', 'id'))
                            ->default(fn (CurrentStoreService $currentStoreService): ?int => $currentStoreService->getId())
                            ->required(fn (): bool => static::currentUser()?->isSuperAdmin() ?? false)
                            ->searchable()
                            ->preload()
                            ->visible(fn (): bool => static::currentUser()?->isSuperAdmin() ?? false)
                            ->dehydrated(fn (): bool => static::currentUser()?->isSuperAdmin() ?? false),

                        Forms\Components\Hidden::make('store_id')
                            ->default(fn (CurrentStoreService $currentStoreService): ?int => $currentStoreService->getId())
                            ->dehydrated(fn (): bool => ! (static::currentUser()?->isSuperAdmin() ?? false)),

                        Forms\Components\Select::make('shift_type')
                            ->label('Tipe Shift')
                            ->options([
                                'morning' => 'Pagi',
                                'evening' => 'Sore',
                            ])
                            ->required()
                            ->disabledOn('edit'),

                        Forms\Components\DatePicker::make('shift_date')
                            ->label('Tanggal')
                            ->required()
                            ->default(now())
                            ->disabledOn('edit'),

                        Forms\Components\TextInput::make('opening_cash')
                            ->label('Uang di Laci (Awal)')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->disabledOn('edit'),
                    ])
                    ->columns(2)
                    ->visibleOn('create'),

                Forms\Components\Section::make('Tutup Shift')
                    ->schema([
                        Forms\Components\TextInput::make('closing_cash')
                            ->label('Uang di Laci (Akhir)')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),

                        Forms\Components\TextInput::make('expected_cash')
                            ->label('Uang yang Seharusnya')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('difference')
                            ->label('Selisih')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('calculate')
                                    ->label('Hitung')
                                    ->icon('heroicon-m-calculator')
                                    ->action(function (Forms\Set $set, Forms\Get $get, $record) {
                                        if ($record) {
                                            $expected = ShiftService::calculateExpectedCash($record->id);
                                            $closing = $get('closing_cash') ?? 0;
                                            $set('expected_cash', $expected);
                                            $set('difference', $closing - $expected);
                                        }
                                    })
                            ),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record && $record->isOpen()),

                Forms\Components\Section::make('Ringkasan Shift')
                    ->schema([
                        Forms\Components\Placeholder::make('summary')
                            ->label('')
                            ->content(function ($record) {
                                if (! $record) {
                                    return 'Simpan shift terlebih dahulu untuk melihat ringkasan.';
                                }

                                $summary = ShiftService::getShiftSummary($record->id);

                                return new \Illuminate\Support\HtmlString(sprintf(
                                    '
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="p-4 bg-primary-50 rounded-lg">
                                            <div class="text-sm text-gray-600">Total Penjualan</div>
                                            <div class="text-2xl font-bold text-primary-600">Rp %s</div>
                                        </div>
                                        <div class="p-4 bg-success-50 rounded-lg">
                                            <div class="text-sm text-gray-600">Total Transaksi</div>
                                            <div class="text-2xl font-bold text-success-600">%s</div>
                                        </div>
                                        <div class="p-4 bg-warning-50 rounded-lg">
                                            <div class="text-sm text-gray-600">Penjualan Tunai</div>
                                            <div class="text-2xl font-bold text-warning-600">Rp %s</div>
                                        </div>
                                        <div class="p-4 bg-danger-50 rounded-lg">
                                            <div class="text-sm text-gray-600">Total Pengeluaran</div>
                                            <div class="text-2xl font-bold text-danger-600">Rp %s</div>
                                        </div>
                                    </div>
                                ',
                                    number_format($summary['total_sales'], 0, ',', '.'),
                                    $summary['total_transactions'],
                                    number_format($summary['cash_sales'], 0, ',', '.'),
                                    number_format($summary['total_expenses'], 0, ',', '.')
                                ));
                            })
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record && $record->isClosed()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Kasir')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('shift_type')
                    ->label('Shift')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'morning' => 'Pagi',
                        'evening' => 'Sore',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'morning' => 'warning',
                        'evening' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('shift_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Aktif',
                        'closed' => 'Selesai',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('opening_cash')
                    ->label('Modal Awal')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expected_cash')
                    ->label('Uang Harusnya')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('closing_cash')
                    ->label('Modal Akhir')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('difference')
                    ->label('Selisih')
                    ->money('IDR')
                    ->sortable()
                    ->color(fn ($record) => $record->difference > 0 ? 'success' : ($record->difference < 0 ? 'danger' : 'gray'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('opened_at')
                    ->label('Buka')
                    ->dateTime('H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('closed_at')
                    ->label('Tutup')
                    ->dateTime('H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('shift_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('shift_type')
                    ->label('Tipe Shift')
                    ->options([
                        'morning' => 'Pagi',
                        'evening' => 'Sore',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Aktif',
                        'closed' => 'Selesai',
                    ]),

                Tables\Filters\SelectFilter::make('store_id')
                    ->label('Toko')
                    ->options(Store::pluck('name', 'id'))
                    ->searchable()
                    ->visible(fn (): bool => static::currentUser()?->isSuperAdmin() ?? false),

                Tables\Filters\Filter::make('shift_date')
                    ->label('Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('date_from'),
                        Forms\Components\DatePicker::make('date_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('shift_date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('shift_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat'),
                Tables\Actions\EditAction::make()
                    ->label('Tutup Shift')
                    ->visible(fn ($record) => $record->isOpen())
                    ->mutateFormDataUsing(function (array $data, $record): array {
                        $data['expected_cash'] = ShiftService::calculateExpectedCash($record->id);
                        $data['difference'] = $data['closing_cash'] - $data['expected_cash'];
                        $data['status'] = 'closed';
                        $data['closed_at'] = now();

                        return $data;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // No bulk actions for shifts
                ]),
            ])
            ->emptyStateHeading('Belum ada shift')
            ->emptyStateDescription('Mulai buka shift untuk memulai transaksi.');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user')
            ->orderBy('shift_date', 'desc');
    }

    protected static function currentUser(): ?User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        return $user;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShifts::route('/'),
            'create' => Pages\CreateShift::route('/create'),
            'view' => Pages\ViewShift::route('/{record}'),
            'edit' => Pages\EditShift::route('/{record}/edit'),
        ];
    }
}
