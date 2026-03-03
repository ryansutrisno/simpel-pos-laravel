<?php

namespace App\Filament\Widgets;

use App\Services\StaffPerformanceService;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;

class TopStaffWidget extends Widget
{
    use HasWidgetShield;

    protected static ?int $sort = 13;

    protected static string $view = 'filament.widgets.top-staff';

    protected int|string|array $columnSpan = 'full';

    public function getTopStaff()
    {
        return StaffPerformanceService::getTopStaff(3, today());
    }

    public static function canView(): bool
    {
        return auth()->user()->can('view_any_user'); // Allow for users who can view users
    }
}
