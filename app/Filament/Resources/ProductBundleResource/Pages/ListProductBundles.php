<?php

namespace App\Filament\Resources\ProductBundleResource\Pages;

use App\Filament\Resources\ProductBundleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductBundles extends ListRecords
{
    protected static string $resource = ProductBundleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
