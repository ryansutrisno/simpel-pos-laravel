<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@pos.test',
                'role' => 'super_admin',
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@pos.test',
                'role' => 'admin',
            ],
            [
                'name' => 'Manager',
                'email' => 'manager@pos.test',
                'role' => 'manager',
            ],
            [
                'name' => 'Kasir',
                'email' => 'kasir@pos.test',
                'role' => 'kasir',
            ],
        ];

        $this->call(ShieldSeeder::class);

        $this->call(VariantBundleDemoSeeder::class);

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );

            $role = Role::where('name', $userData['role'])->first();
            if ($role && ! $user->hasRole($userData['role'])) {
                $user->assignRole($role);
            }
        }

        $customers = [
            ['name' => 'Budi Santoso', 'phone' => '081234567890', 'email' => 'budi@email.com', 'address' => 'Jl. Merdeka No. 10'],
            ['name' => 'Siti Rahayu', 'phone' => '082345678901', 'email' => 'siti@email.com', 'address' => 'Jl. Sudirman No. 25'],
            ['name' => 'Ahmad Hidayat', 'phone' => '083456789012', 'email' => null, 'address' => 'Jl. Gatot Subroto No. 5'],
            ['name' => 'Dewi Lestari', 'phone' => '084567890123', 'email' => 'dewi@email.com', 'address' => null],
            ['name' => 'Rudi Hartono', 'phone' => '085678901234', 'email' => null, 'address' => null],
        ];

        foreach ($customers as $customerData) {
            Customer::firstOrCreate(
                ['phone' => $customerData['phone']],
                $customerData
            );
        }
    }
}
