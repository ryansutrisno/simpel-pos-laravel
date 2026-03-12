<?php

namespace App\Filament\Resources\ShiftResource\Pages;

use App\Filament\Resources\ShiftResource;
use App\Models\User;
use App\Services\ShiftService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListShifts extends ListRecords
{
    protected static string $resource = ShiftResource::class;

    protected static ?string $title = 'Daftar Shift';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buka Shift')
                ->visible(fn () => ShiftService::canOpenShift(Auth::id())),
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        return $this->getEloquentQuery();
    }

    protected function getEloquentQuery(): Builder
    {
        $query = ShiftResource::getEloquentQuery();
        $user = Auth::user();

        if (! $user instanceof User || ! $user->isSuperAdmin()) {
            return $query->forCurrentStore();
        }

        return $query;
    }
}
