<?php

namespace App\Filament\Resources\ReceiptTemplateResource\Pages;

use App\Filament\Resources\ReceiptTemplateResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class PreviewReceiptTemplate extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ReceiptTemplateResource::class;

    protected static string $view = 'filament.resources.receipt-template-resource.pages.preview-receipt-template';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }
}
