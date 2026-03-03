<?php

namespace App\Filament\Resources\ProductBundleResource\Pages;

use App\Filament\Resources\ProductBundleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProductBundle extends ViewRecord
{
    protected static string $resource = ProductBundleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
