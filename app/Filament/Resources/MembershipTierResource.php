<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MembershipTierResource\Pages;
use App\Models\MembershipTier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MembershipTierResource extends Resource
{
    protected static ?string $model = MembershipTier::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Tier Membership';

    protected static ?string $pluralLabel = 'Tier Membership';

    protected static ?string $navigationGroup = 'Manajemen';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Tier')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Tier')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Urutan')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pengaturan Poin')
                    ->schema([
                        Forms\Components\TextInput::make('min_spent')
                            ->label('Minimum Total Belanja')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        Forms\Components\TextInput::make('multiplier')
                            ->label('Multiplier Poin')
                            ->required()
                            ->numeric()
                            ->step(0.1)
                            ->default(1.0),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Tampilan')
                    ->schema([
                        Forms\Components\ColorPicker::make('color')
                            ->label('Warna')
                            ->default('#CD7F32'),
                        Forms\Components\TextInput::make('icon')
                            ->label('Icon (Heroicon)')
                            ->placeholder('heroicon-o-star')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Keuntungan')
                    ->schema([
                        Forms\Components\Repeater::make('benefits')
                            ->label('Daftar Keuntungan')
                            ->schema([
                                Forms\Components\TextInput::make('benefit')
                                    ->label('Keuntungan')
                                    ->required(),
                            ])
                            ->columns(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_spent')
                    ->label('Min. Belanja')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('multiplier')
                    ->label('Multiplier')
                    ->formatStateUsing(fn ($state): string => $state.'x')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('colorPreview')
                    ->label('Warna')
                    ->html()
                    ->state(fn (MembershipTier $record): string => '<div style="display:inline-flex;align-items:center;gap:6px;"><span style="display:inline-block;width:20px;height:20px;border-radius:4px;background-color:'.$record->color.';flex-shrink:0;"></span><span>'.$record->color.'</span></div>'),
                Tables\Columns\TextColumn::make('benefits')
                    ->label('Keuntungan')
                    ->lineClamp(1),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->before(fn ($action) => $action->record->customers()->count() > 0
                        ? $action->cancelWithNotification([
                            'title' => 'Tidak dapat dihapus',
                            'body' => 'Tier ini memiliki '.$action->record->customers()->count().' pelanggan.',
                            'color' => 'danger',
                        ])
                        : null
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
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
            'index' => Pages\ListMembershipTiers::route('/'),
            'create' => Pages\CreateMembershipTier::route('/create'),
            'view' => Pages\ViewMembershipTier::route('/{record}'),
            'edit' => Pages\EditMembershipTier::route('/{record}/edit'),
        ];
    }
}
