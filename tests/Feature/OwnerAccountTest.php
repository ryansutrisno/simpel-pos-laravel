<?php

use App\Models\User;
use Database\Seeders\OwnerAccountSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;

it('creates and safely re-runs the permanent owner account seeder', function () {
    $originalOwnerConfig = config('services.owner');
    config([
        'services.owner.email' => 'owner-test@example.com',
        'services.owner.password' => 'owner-test-password',
    ]);

    try {
        $this->artisan('db:seed', [
            '--class' => OwnerAccountSeeder::class,
            '--force' => true,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Created owner account: owner-test@example.com')
            ->expectsOutputToContain('Role ensured: super_admin.');

        $owner = User::query()->where('email', 'owner-test@example.com')->firstOrFail();
        $originalPassword = $owner->password;

        expect($owner->hasRole('super_admin'))->toBeTrue()
            ->and($owner->email_verified_at)->not->toBeNull()
            ->and($owner->is_demo_account)->toBeFalse()
            ->and($owner->demo_access_expires_at)->toBeNull()
            ->and(Hash::check('owner-test-password', $owner->password))->toBeTrue();

        $this->artisan('db:seed', [
            '--class' => OwnerAccountSeeder::class,
            '--force' => true,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Owner account already existed (unchanged): owner-test@example.com')
            ->expectsOutputToContain('Role ensured: super_admin.');

        expect($owner->refresh()->password)->toBe($originalPassword)
            ->and(Hash::check('owner-test-password', $owner->password))->toBeTrue();
    } finally {
        config(['services.owner' => $originalOwnerConfig]);
    }
});

it('stays usable when default demo access commands target demo accounts', function () {
    $originalOwnerConfig = config('services.owner');
    config([
        'services.owner.email' => 'owner-test@example.com',
        'services.owner.password' => 'owner-test-password',
    ]);

    try {
        $this->artisan('db:seed', [
            '--class' => OwnerAccountSeeder::class,
            '--force' => true,
        ])->assertSuccessful();

        $owner = User::query()->where('email', 'owner-test@example.com')->firstOrFail();
        $demoUser = User::factory()->create([
            'email' => 'demo-target@pos.test',
            'is_demo_account' => true,
            'demo_access_expires_at' => now()->addHour(),
        ]);
        $demoUser->assignRole($owner->roles()->first());

        $this->artisan('security:demo-access', ['--disable' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Disabled demo access for 1 account(s).');

        $owner->refresh();
        $demoUser->refresh();
        $panel = Filament::getPanel('admin');

        expect($owner->email)->not->toMatch('/@pos\.test$/')
            ->and($owner->canAccessPanel($panel))->toBeTrue()
            ->and($demoUser->canAccessPanel($panel))->toBeFalse()
            ->and(Hash::check('owner-test-password', $owner->password))->toBeTrue();

        $this->artisan('security:rotate-demo-passwords')
            ->assertSuccessful()
            ->expectsOutputToContain('Updated 1 account(s).');

        expect(Hash::check('owner-test-password', $owner->refresh()->password))->toBeTrue();
    } finally {
        config(['services.owner' => $originalOwnerConfig]);
    }
});

it('reads owner settings through the config layer', function () {
    expect(file_get_contents(base_path('database/seeders/OwnerAccountSeeder.php')))
        ->not->toContain('env(');
});
