<?php

namespace App\Filament\Resources\PrinterConfigResource\Pages;

use App\Filament\Resources\PrinterConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrinterConfigs extends ListRecords
{
    protected static string $resource = PrinterConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
