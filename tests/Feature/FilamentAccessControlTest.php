<?php

use App\Models\AppSettings;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentGatewayConfig;
use App\Models\PrinterConfig;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $kasirRole = Role::firstOrCreate(['name' => 'kasir', 'guard_name' => 'web']);
    $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $this->kasir = User::factory()->create();
    $this->kasir->assignRole($kasirRole);

    $this->admin = User::factory()->create();
    $this->admin->assignRole($adminRole);
});

it('denies kasir access to sensitive resources', function (string $modelClass) {
    expect(Gate::forUser($this->kasir)->allows('viewAny', $modelClass))->toBeFalse();
})->with([
    AppSettings::class,
    Expense::class,
    ExpenseCategory::class,
    PaymentGatewayConfig::class,
    PrinterConfig::class,
]);

it('allows admin access to sensitive resources', function (string $modelClass) {
    expect(Gate::forUser($this->admin)->allows('viewAny', $modelClass))->toBeTrue();
})->with([
    AppSettings::class,
    Expense::class,
    ExpenseCategory::class,
    PaymentGatewayConfig::class,
    PrinterConfig::class,
]);

it('restricts panel access to users with an allowed role', function () {
    $panel = Filament::getPanel('admin');
    $unassignedUser = User::factory()->create();

    expect($unassignedUser->canAccessPanel($panel))->toBeFalse()
        ->and($this->kasir->canAccessPanel($panel))->toBeTrue();
});

it('does not expose demo credentials on the login page', function () {
    $this->get('/admin/login')
        ->assertSuccessful()
        ->assertDontSee('superadmin@pos.test')
        ->assertDontSee('demo-account-')
        ->assertDontSee('Password: password');
});
