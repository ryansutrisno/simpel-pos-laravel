<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->kasir = User::where('email', 'kasir@pos.test')->first();
    if (! $this->kasir) {
        $this->kasir = User::factory()->create([
            'email' => 'kasir@pos.test',
        ]);
        $kasirRole = Role::where('name', 'kasir')->first();
        if ($kasirRole) {
            $this->kasir->assignRole($kasirRole);
        }
    }
});

it('kasir cannot view app settings', function () {
    $this->actingAs($this->kasir);

    expect($this->kasir->can('view_any_app::settings'))->toBeFalse();
});

it('kasir cannot view printer config', function () {
    $this->actingAs($this->kasir);

    expect($this->kasir->can('view_any_printer::config'))->toBeFalse();
});

it('kasir cannot view payment gateway config', function () {
    $this->actingAs($this->kasir);

    expect($this->kasir->can('view_any_payment::gateway::config'))->toBeFalse();
});

it('kasir can view products', function () {
    $this->actingAs($this->kasir);

    expect($this->kasir->can('view_product'))->toBeTrue();
});

it('kasir can view transactions', function () {
    $this->actingAs($this->kasir);

    expect($this->kasir->can('view_transaction'))->toBeTrue();
});

it('kasir can create transactions', function () {
    $this->actingAs($this->kasir);

    expect($this->kasir->can('create_transaction'))->toBeTrue();
});

it('admin can view app settings', function () {
    $admin = User::where('email', 'admin@pos.test')->first();
    if (! $admin) {
        $admin = User::factory()->create([
            'email' => 'admin@pos.test',
        ]);
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $admin->assignRole($adminRole);
        }
    }

    $this->actingAs($admin);

    expect($admin->can('view_any_app::settings'))->toBeTrue();
});

it('admin can view printer config', function () {
    $admin = User::where('email', 'admin@pos.test')->first();
    if (! $admin) {
        $admin = User::factory()->create([
            'email' => 'admin@pos.test',
        ]);
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $admin->assignRole($adminRole);
        }
    }

    $this->actingAs($admin);

    expect($admin->can('view_any_printer::config'))->toBeTrue();
});

it('admin can view payment gateway config', function () {
    $admin = User::where('email', 'admin@pos.test')->first();
    if (! $admin) {
        $admin = User::factory()->create([
            'email' => 'admin@pos.test',
        ]);
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $admin->assignRole($adminRole);
        }
    }

    $this->actingAs($admin);

    expect($admin->can('view_any_payment::gateway::config'))->toBeTrue();
});
