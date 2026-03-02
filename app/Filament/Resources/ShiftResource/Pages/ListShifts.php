<?php

namespace App\Filament\Resources\ShiftResource\Pages;

use App\Filament\Resources\ShiftResource;
use App\Services\ShiftService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShifts extends ListRecords
{
    protected static string $resource = ShiftResource::class;

    protected static ?string $title = 'Daftar Shift';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buka Shift')
                ->visible(fn () => ShiftService::canOpenShift(auth()->id())),
        ];
    }
}
