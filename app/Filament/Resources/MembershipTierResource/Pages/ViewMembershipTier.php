<?php

namespace App\Filament\Resources\MembershipTierResource\Pages;

use App\Filament\Resources\MembershipTierResource;
use App\Models\Customer;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class ViewMembershipTier extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = MembershipTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->before(fn ($action) => $action->record->customers()->count() > 0
                    ? $action->cancelWithNotification([
                        'title' => 'Tidak dapat dihapus',
                        'body' => 'Tier ini memiliki '.$action->record->customers()->count().' pelanggan.',
                        'color' => 'danger',
                    ])
                    : null
                ),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                Section::make('Informasi Tier')
                    ->schema([
                        TextEntry::make('name')->label('Nama Tier'),
                        TextEntry::make('slug')->label('Slug'),
                        TextEntry::make('min_spent')
                            ->label('Minimum Total Belanja')
                            ->money('IDR'),
                        TextEntry::make('multiplier')
                            ->label('Multiplier Poin')
                            ->formatStateUsing(fn ($state): string => $state.'x'),
                        TextEntry::make('colorPreview')
                            ->label('Warna')
                            ->html()
                            ->state(fn ($record): string => '<div style="display:inline-flex;align-items:center;gap:8px;"><span style="display:inline-block;width:32px;height:32px;border-radius:6px;background-color:'.$record->color.';flex-shrink:0;"></span><span>'.$record->color.'</span></div>'),
                        TextEntry::make('icon')->label('Icon')->default('-'),
                        TextEntry::make('sort_order')->label('Urutan'),
                    ])
                    ->columns(3),

                Section::make('Keuntungan')
                    ->schema([
                        TextEntry::make('benefits')
                            ->label('Daftar Keuntungan')
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) {
                                    return '-';
                                }

                                return collect($state)->map(fn ($item, $index) => ($index + 1).'. '.$item)->implode('<br>');
                            })
                            ->html()
                            ->lineClamp(5),
                    ]),

                Section::make('Status')
                    ->schema([
                        TextEntry::make('is_active')
                            ->label('Status')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Tidak Aktif'),
                    ]),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return Customer::query()
            ->where('membership_tier_id', $this->record->id)
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label('Nama')
                ->searchable(),
            Tables\Columns\TextColumn::make('phone')
                ->label('Telepon')
                ->searchable(),
            Tables\Columns\TextColumn::make('points')
                ->label('Poin')
                ->badge()
                ->color('success'),
            Tables\Columns\TextColumn::make('total_spent')
                ->label('Total Belanja')
                ->money('IDR'),
            Tables\Columns\TextColumn::make('total_transactions')
                ->label('Total Transaksi')
                ->badge(),
        ];
    }
}
