<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewExpense extends ViewRecord
{
    protected static string $resource = ExpenseResource::class;

    protected static ?string $title = 'Detail Pengeluaran';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Ubah'),
            Actions\DeleteAction::make()->label('Hapus'),
        ];
    }
}
