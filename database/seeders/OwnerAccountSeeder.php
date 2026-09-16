<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class OwnerAccountSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('services.owner.email') ?: 'owner@trazmedia.com';
        $configuredPassword = config('services.owner.password');
        $hasConfiguredPassword = is_string($configuredPassword) && $configuredPassword !== '';
        $owner = User::query()->where('email', $email)->first();

        if ($owner === null) {
            $password = $hasConfiguredPassword ? $configuredPassword : Str::password(20);

            $owner = User::create([
                'name' => 'Owner',
                'email' => $email,
                'password' => $password,
                'email_verified_at' => now(),
            ]);

            $this->command->info("Created owner account: {$email}");

            if (! $hasConfiguredPassword) {
                $this->command->warn('OWNER_PASSWORD is empty. Store this generated password safely and set OWNER_PASSWORD before the next deployment.');
                $this->command->line("Generated OWNER_PASSWORD: {$password}");
            }
        } else {
            $this->command->info("Owner account already existed (unchanged): {$email}");

            if ($owner->email_verified_at === null) {
                $owner->forceFill(['email_verified_at' => now()])->save();
                $this->command->info('Owner email marked as verified.');
            }
        }

        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        if (! $owner->hasRole('super_admin')) {
            $owner->assignRole($role);
        }

        $this->command->info('Role ensured: super_admin.');
    }
}
