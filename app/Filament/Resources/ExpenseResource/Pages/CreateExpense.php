<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected static ?string $title = 'Tambah Pengeluaran';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['expense_number'] = \App\Services\ExpenseService::generateExpenseNumber();

        // Link to active shift if available
        $activeShift = auth()->user()->activeShift();
        if ($activeShift) {
            $data['shift_id'] = $activeShift->id;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pengeluaran berhasil ditambahkan';
    }
}
