<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case OPERATIONAL = 'operational';
    case SUPPLIES = 'supplies';
    case SALARY = 'salary';
    case MAINTENANCE = 'maintenance';
    case RENT = 'rent';
    case UTILITIES = 'utilities';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::OPERATIONAL => 'Operasional',
            self::SUPPLIES => 'Perlengkapan',
            self::SALARY => 'Gaji',
            self::MAINTENANCE => 'Perbaikan',
            self::RENT => 'Sewa',
            self::UTILITIES => 'Listrik & Air',
            self::OTHER => 'Lainnya',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::OPERATIONAL => '#3B82F6',
            self::SUPPLIES => '#10B981',
            self::SALARY => '#8B5CF6',
            self::MAINTENANCE => '#F59E0B',
            self::RENT => '#EF4444',
            self::UTILITIES => '#06B6D4',
            self::OTHER => '#6B7280',
        };
    }

    public static function getDefault(): self
    {
        return self::OPERATIONAL;
    }
}
