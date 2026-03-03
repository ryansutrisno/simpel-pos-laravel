<?php

namespace App\Filament\Resources\ProductBundleResource\Pages;

use App\Filament\Resources\ProductBundleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductBundle extends EditRecord
{
    protected static string $resource = ProductBundleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
