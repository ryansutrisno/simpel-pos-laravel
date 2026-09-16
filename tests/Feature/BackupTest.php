<?php

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('backups');
});

it('can run backup command', function () {
    $this->artisan('backup:run', ['--only-db' => true])
        ->assertSuccessful();
});

it('can run full backup command', function () {
    $this->artisan('backup:run')
        ->assertSuccessful();
});

it('can run cleanup command', function () {
    $this->artisan('backup:clean')
        ->assertSuccessful();
});

it('backup page is accessible', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

    $response = $this->actingAs($user)->get('/admin/backups');

    $response->assertOk();
});

it('backup file can be downloaded', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

    // Create a fake backup file
    Storage::disk('backups')->put('Laravel/test-backup.zip', 'fake content');

    $response = $this->actingAs($user)->get('/admin/backups/download/Laravel/test-backup.zip');

    $response->assertOk();
    $response->assertDownload('test-backup.zip');

    $this->actingAs($user)
        ->get('/admin/backups/download/../test-backup.zip')
        ->assertNotFound();
});

it('shows backup list in admin panel', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

    // Create fake backup files
    Storage::disk('backups')->put('Laravel/2026-02-27-05-00-00.zip', 'fake content 1');
    Storage::disk('backups')->put('Laravel/2026-02-27-06-00-00.zip', 'fake content 2');

    $response = $this->actingAs($user)->get('/admin/backups');

    $response->assertOk();
    // The page should load without errors
    $response->assertSee('Backup');
});
