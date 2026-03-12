<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Hapus Actions\CreateAction untuk mencegah pembuatan transaksi dari sini
        ];
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->modifyQueryUsing(function (Builder $query): Builder {
                $user = Auth::user();

                if ($user?->isSuperAdmin() ?? false) {
                    return $query;
                }

                return $query->forCurrentStore();
            });
    }
}
