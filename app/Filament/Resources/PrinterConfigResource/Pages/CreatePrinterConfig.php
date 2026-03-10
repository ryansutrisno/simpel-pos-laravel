<?php

namespace App\Filament\Resources\PrinterConfigResource\Pages;

use App\Filament\Resources\PrinterConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePrinterConfig extends CreateRecord
{
    protected static string $resource = PrinterConfigResource::class;
}
