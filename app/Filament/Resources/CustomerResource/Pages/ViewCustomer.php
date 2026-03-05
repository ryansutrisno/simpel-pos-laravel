<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\CustomerPoint;
use App\Services\MembershipTierService;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class ViewCustomer extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Ubah'),
            Actions\DeleteAction::make()->label('Hapus'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $tierService = app(MembershipTierService::class);
        $nextTier = $tierService->getNextTier($this->record);
        $pointsToNext = $tierService->getPointsToNextTier($this->record);

        $schema = [
            Section::make('Informasi Pelanggan')
                ->schema([
                    TextEntry::make('name')->label('Nama'),
                    TextEntry::make('phone')->label('Telepon'),
                    TextEntry::make('email')->label('Email')->default('-'),
                    TextEntry::make('address')->label('Alamat')->default('-'),
                ])
                ->columns(2),
        ];

        if ($this->record->membershipTier) {
            $tierColor = $this->record->membershipTier->color ?? '#6b7280';
            $tierSchema = [
                TextEntry::make('tier_badge')
                    ->label('Tier Saat Ini')
                    ->html()
                    ->state(fn () => '<span style="display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:9999px;font-size:14px;font-weight:500;background-color:'.$tierColor.'20;color:'.$tierColor.';border:1px solid '.$tierColor.'40;">
                        <span style="width:8px;height:8px;border-radius:50%;background-color:'.$tierColor.';"></span>
                        '.$this->record->membershipTier->name.'
                    </span>'),
                TextEntry::make('membershipTier.multiplier')
                    ->label('Multiplier Poin')
                    ->formatStateUsing(fn ($state): string => $state.'x'),
            ];

            if ($nextTier) {
                $tierSchema[] = TextEntry::make('next_tier')
                    ->label('Tier Berikutnya')
                    ->default($nextTier->name.' (Rp '.number_format($nextTier->min_spent, 0, ',', '.').')');
                $tierSchema[] = TextEntry::make('points_to_next')
                    ->label('Sisa Belanja ke Tier Berikutnya')
                    ->default('Rp '.number_format($pointsToNext, 0, ',', '.'));
            }

            $schema[] = Section::make(' keanggotaan')
                ->schema($tierSchema)
                ->columns($nextTier ? 4 : 2);
        }

        $schema[] = Section::make('Statistik')
            ->schema([
                TextEntry::make('points')
                    ->label('Poin Saat Ini')
                    ->badge()
                    ->color('success'),
                TextEntry::make('total_spent')
                    ->label('Total Belanja')
                    ->money('IDR'),
                TextEntry::make('total_transactions')
                    ->label('Total Transaksi')
                    ->badge(),
                TextEntry::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Tidak Aktif'),
            ])
            ->columns(4);

        return $infolist
            ->record($this->record)
            ->schema($schema);
    }

    protected function getTableQuery(): Builder
    {
        return CustomerPoint::query()
            ->where('customer_id', $this->record->id)
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('created_at')
                ->label('Tanggal')
                ->dateTime('d M Y H:i')
                ->sortable(),
            Tables\Columns\TextColumn::make('type')
                ->label('Tipe')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'earn' => 'success',
                    'redeem' => 'warning',
                    'adjust' => 'info',
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'earn' => 'Diperoleh',
                    'redeem' => 'Ditukar',
                    'adjust' => 'Penyesuaian',
                }),
            Tables\Columns\TextColumn::make('amount')
                ->label('Jumlah Poin')
                ->formatStateUsing(fn (CustomerPoint $record): string => ($record->type === 'redeem' ? '-' : '+').$record->amount),
            Tables\Columns\TextColumn::make('balance_after')
                ->label('Saldo Akhir')
                ->badge(),
            Tables\Columns\TextColumn::make('description')
                ->label('Keterangan')
                ->default('-'),
        ];
    }
}
