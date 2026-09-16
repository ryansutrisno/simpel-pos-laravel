<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('does not change passwords during a dry run', function () {
    $user = User::factory()->create([
        'email' => 'dry-run@pos.test',
        'password' => 'password',
    ]);

    $this->artisan('security:rotate-demo-passwords', [
        '--email' => ['dry-run@pos.test'],
        '--dry-run' => true,
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('dry-run@pos.test')
        ->expectsOutputToContain('Dry run complete')
        ->doesntExpectOutputToContain('New Password');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

it('blocks password rotation in production without force', function () {
    $user = User::factory()->create([
        'email' => 'production-guard@pos.test',
        'password' => 'password',
    ]);

    app()->detectEnvironment(fn (): string => 'production');

    try {
        $this->artisan('security:rotate-demo-passwords', [
            '--email' => ['production-guard@pos.test'],
        ])
            ->assertFailed()
            ->expectsOutputToContain('blocked in production');
    } finally {
        app()->detectEnvironment(fn (): string => 'testing');
    }

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

it('rotates and prints a new password for a targeted account', function () {
    $user = User::factory()->create([
        'email' => 'rotate@pos.test',
        'password' => 'password',
    ]);

    $this->artisan('security:rotate-demo-passwords', [
        '--email' => ['rotate@pos.test'],
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('rotate@pos.test')
        ->expectsOutputToContain('New Password')
        ->expectsOutputToContain('Updated 1 account(s).');

    expect(Hash::check('password', $user->refresh()->password))->toBeFalse();
});

it('reports an unknown targeted email', function () {
    $this->artisan('security:rotate-demo-passwords', [
        '--email' => ['unknown@pos.test'],
    ])
        ->assertFailed()
        ->expectsOutputToContain('No user found for email: unknown@pos.test');
});
