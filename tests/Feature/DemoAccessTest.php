<?php

use App\Models\User;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $kasirRole = Role::firstOrCreate(['name' => 'kasir', 'guard_name' => 'web']);
    $this->kasirRole = $kasirRole;
    $this->panel = Filament::getPanel('admin');
});

it('DemoAccess status does not change the selected account', function () {
    $user = User::factory()->create([
        'email' => 'status@pos.test',
        'is_demo_account' => true,
        'demo_access_expires_at' => now()->addHour(),
    ]);

    $user->assignRole($this->kasirRole);
    $beforeExpiry = $user->demo_access_expires_at;

    $this->artisan('security:demo-access', [
        '--email' => ['status@pos.test'],
        '--status' => true,
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('No changes made.');

    expect($user->refresh()->is_demo_account)->toBeTrue()
        ->and($user->demo_access_expires_at->equalTo($beforeExpiry))->toBeTrue();
});

it('DemoAccess blocks changes in production without force', function () {
    $user = User::factory()->create(['email' => 'production@pos.test']);

    app()->detectEnvironment(fn (): string => 'production');

    try {
        $this->artisan('security:demo-access', [
            '--email' => ['production@pos.test'],
            '--enable' => '2h',
        ])
            ->assertFailed()
            ->expectsOutputToContain('blocked in production');
    } finally {
        app()->detectEnvironment(fn (): string => 'testing');
    }

    expect($user->refresh()->is_demo_account)->toBeFalse()
        ->and($user->demo_access_expires_at)->toBeNull();
});

it('DemoAccess enables a demo account for the requested duration', function () {
    $user = User::factory()->create(['email' => 'enable@pos.test']);
    $user->assignRole($this->kasirRole);

    $this->artisan('security:demo-access', [
        '--email' => ['enable@pos.test'],
        '--enable' => '2h',
    ])->assertSuccessful();

    $user->refresh();

    expect($user->is_demo_account)->toBeTrue()
        ->and($user->demo_access_expires_at->isFuture())->toBeTrue()
        ->and($user->canAccessPanel($this->panel))->toBeTrue();
});

it('DemoAccess denies a demo account after expiry', function () {
    $user = User::factory()->create([
        'email' => 'expired@pos.test',
        'is_demo_account' => true,
        'demo_access_expires_at' => now()->subMinute(),
    ]);
    $user->assignRole($this->kasirRole);

    expect($user->canAccessPanel($this->panel))->toBeFalse();
});

it('DemoAccess disable immediately denies an active demo account', function () {
    $user = User::factory()->create([
        'email' => 'disable@pos.test',
        'is_demo_account' => true,
        'demo_access_expires_at' => now()->addHour(),
    ]);
    $user->assignRole($this->kasirRole);

    $this->artisan('security:demo-access', [
        '--email' => ['disable@pos.test'],
        '--disable' => true,
    ])->assertSuccessful();

    $user->refresh();

    expect($user->is_demo_account)->toBeTrue()
        ->and($user->demo_access_expires_at)->toBeNull()
        ->and($user->canAccessPanel($this->panel))->toBeFalse();
});

it('DemoAccess promotes a demo account back to normal staff access', function () {
    $user = User::factory()->create([
        'email' => 'promote@pos.test',
        'is_demo_account' => true,
        'demo_access_expires_at' => now()->addHour(),
    ]);
    $user->assignRole($this->kasirRole);

    $this->artisan('security:demo-access', [
        '--email' => ['promote@pos.test'],
        '--promote' => true,
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('Not a demo account');

    $user->refresh();

    expect($user->is_demo_account)->toBeFalse()
        ->and($user->demo_access_expires_at)->toBeNull()
        ->and($user->canAccessPanel($this->panel))->toBeTrue();
});

it('DemoAccess requires an explicit email when promoting', function () {
    $user = User::factory()->create([
        'email' => 'promote-required@pos.test',
        'is_demo_account' => true,
        'demo_access_expires_at' => now()->addHour(),
    ]);
    $user->assignRole($this->kasirRole);

    $this->artisan('security:demo-access', ['--promote' => true])
        ->assertFailed()
        ->expectsOutputToContain('requires at least one explicit --email=')
        ->expectsOutputToContain('silently unlock the entire demo set');

    expect($user->refresh()->is_demo_account)->toBeTrue()
        ->and($user->demo_access_expires_at->isFuture())->toBeTrue();
});

it('DemoAccess blocks promotion in production without force', function () {
    $user = User::factory()->create([
        'email' => 'promote-production@pos.test',
        'is_demo_account' => true,
        'demo_access_expires_at' => now()->addHour(),
    ]);
    $user->assignRole($this->kasirRole);

    app()->detectEnvironment(fn (): string => 'production');

    try {
        $this->artisan('security:demo-access', [
            '--email' => ['promote-production@pos.test'],
            '--promote' => true,
        ])
            ->assertFailed()
            ->expectsOutputToContain('blocked in production');
    } finally {
        app()->detectEnvironment(fn (): string => 'testing');
    }

    expect($user->refresh()->is_demo_account)->toBeTrue()
        ->and($user->demo_access_expires_at->isFuture())->toBeTrue();
});

it('DemoAccess warns when promoting a super admin', function () {
    $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = User::factory()->create([
        'email' => 'promote-admin@pos.test',
        'is_demo_account' => true,
        'demo_access_expires_at' => now()->addHour(),
    ]);
    $user->assignRole($superAdminRole);

    $this->artisan('security:demo-access', [
        '--email' => ['promote-admin@pos.test'],
        '--promote' => true,
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('WARNING: Promoting promote-admin@pos.test')
        ->expectsOutputToContain('security:rotate-demo-passwords --email=promote-admin@pos.test');
});

it('DemoAccess promotion does not target other pos.test accounts', function () {
    $target = User::factory()->create([
        'email' => 'promote-target@pos.test',
        'is_demo_account' => true,
        'demo_access_expires_at' => now()->addHour(),
    ]);
    $other = User::factory()->create([
        'email' => 'promote-other@pos.test',
        'is_demo_account' => true,
        'demo_access_expires_at' => now()->addHour(),
    ]);
    $target->assignRole($this->kasirRole);
    $other->assignRole($this->kasirRole);

    $this->artisan('security:demo-access', [
        '--email' => ['promote-target@pos.test'],
        '--promote' => true,
    ])->assertSuccessful();

    expect($target->refresh()->is_demo_account)->toBeFalse()
        ->and($target->demo_access_expires_at)->toBeNull()
        ->and($other->refresh()->is_demo_account)->toBeTrue()
        ->and($other->demo_access_expires_at->isFuture())->toBeTrue();
});

it('DemoAccess leaves non-demo users unaffected', function () {
    $user = User::factory()->create([
        'email' => 'owner@example.com',
        'is_demo_account' => false,
        'demo_access_expires_at' => now()->subHour(),
    ]);
    User::factory()->create(['email' => 'demo-target@pos.test']);
    $user->assignRole($this->kasirRole);

    $this->artisan('security:demo-access', ['--enable' => '2h'])
        ->assertSuccessful();

    $user->refresh();

    expect($user->is_demo_account)->toBeFalse()
        ->and($user->demo_access_expires_at->isPast())->toBeTrue()
        ->and($user->canAccessPanel($this->panel))->toBeTrue();
});
