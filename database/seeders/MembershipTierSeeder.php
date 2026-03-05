<?php

namespace Database\Seeders;

use App\Models\MembershipTier;
use Illuminate\Database\Seeder;

class MembershipTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Bronze',
                'slug' => 'bronze',
                'min_spent' => 0,
                'multiplier' => 1.0,
                'color' => '#CD7F32',
                'icon' => 'heroicon-o-star',
                'benefits' => [
                    'Mendapatkan poin dari setiap transaksi',
                    'Tukar poin dengan diskon',
                ],
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Silver',
                'slug' => 'silver',
                'min_spent' => 5000000,
                'multiplier' => 1.5,
                'color' => '#C0C0C0',
                'icon' => 'heroicon-o-star',
                'benefits' => [
                    'Mendapatkan poin dari setiap transaksi',
                    'Tukar poin dengan diskon',
                    'Bonus 50% poin lebih banyak',
                ],
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Gold',
                'slug' => 'gold',
                'min_spent' => 20000000,
                'multiplier' => 2.0,
                'color' => '#FFD700',
                'icon' => 'heroicon-o-star',
                'benefits' => [
                    'Mendapatkan poin dari setiap transaksi',
                    'Tukar poin dengan diskon',
                    'Bonus 100% poin lebih banyak',
                    'Prioritas layanan',
                ],
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($tiers as $tier) {
            MembershipTier::updateOrCreate(
                ['slug' => $tier['slug']],
                $tier
            );
        }
    }
}
