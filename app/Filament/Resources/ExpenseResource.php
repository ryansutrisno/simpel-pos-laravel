<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ExpenseService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Manajemen Keuangan';

    protected static ?string $navigationLabel = 'Pengeluaran';

    protected static ?string $pluralLabel = 'Pengeluaran';

    protected static ?string $modelLabel = 'Pengeluaran';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pengeluaran')
                    ->schema([
                        Forms\Components\TextInput::make('expense_number')
                            ->label('Nomor Pengeluaran')
                            ->default(fn () => ExpenseService::generateExpenseNumber())
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Select::make('expense_category_id')
                            ->label('Kategori')
                            ->options(ExpenseCategory::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required(),
                                Forms\Components\Textarea::make('description')
                                    ->rows(2),
                            ])
                            ->createOptionUsing(function (array $data) {
                                return ExpenseCategory::create($data)->getKey();
                            }),

                        Forms\Components\DatePicker::make('expense_date')
                            ->label('Tanggal')
                            ->required()
                            ->default(now()),

                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->maxValue(9999999999999.99),

                        Forms\Components\Textarea::make('description')
                            ->label('Keterangan')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('attachment')
                            ->label('Lampiran')
                            ->image()
                            ->downloadable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expense_number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->category?->color ?? 'gray'),

                Tables\Columns\TextColumn::make('expense_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Dibuat Oleh')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('expense_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('expense_category_id')
                    ->label('Kategori')
                    ->options(ExpenseCategory::pluck('name', 'id')),

                Tables\Filters\Filter::make('expense_date')
                    ->label('Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('date_from'),
                        Forms\Components\DatePicker::make('date_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('expense_date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('expense_date', '<=', $date),
                            );
                    }),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat'),
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()->label('Hapus'),
                Tables\Actions\RestoreAction::make()->label('Pulihkan'),
                Tables\Actions\ForceDeleteAction::make()->label('Hapus Permanen'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Hapus'),
                    Tables\Actions\ForceDeleteBulkAction::make()->label('Hapus Permanen'),
                    Tables\Actions\RestoreBulkAction::make()->label('Pulihkan'),
                ]),
            ])
            ->emptyStateHeading('Belum ada pengeluaran')
            ->emptyStateDescription('Mulai catat pengeluaran toko Anda.');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['category', 'user'])
            ->orderBy('expense_date', 'desc');
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
            'view' => Pages\ViewExpense::route('/{record}'),
        ];
    }
}
