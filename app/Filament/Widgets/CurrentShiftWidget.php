<?php

namespace App\Filament\Widgets;

use App\Services\ShiftService;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;

class CurrentShiftWidget extends Widget
{
    use HasWidgetShield;

    protected static ?int $sort = 1;

    protected static string $view = 'filament.widgets.current-shift';

    protected int|string|array $columnSpan = 'full';

    public function getActiveShift()
    {
        return auth()->user()->activeShift();
    }

    public function getShiftSummary()
    {
        $shift = $this->getActiveShift();

        if (! $shift) {
            return null;
        }

        return ShiftService::getShiftSummary($shift->id);
    }

    public static function canView(): bool
    {
        return true; // All users can see this widget
    }
}
