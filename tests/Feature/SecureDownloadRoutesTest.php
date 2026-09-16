<?php

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

function makeSecureDownloadUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));

    return $user;
}

it('redirects guests to login for protected download routes', function () {
    $this->get('/admin/import-template/download')
        ->assertRedirect('/login');

    $this->get('/admin/backups/download/database.zip')
        ->assertRedirect('/login');
});

it('allows a cashier to download the import template', function () {
    $templatePath = storage_path('app/public/templates/product_import_template.xlsx');
    File::ensureDirectoryExists(dirname($templatePath));
    File::put($templatePath, 'template');

    try {
        $this->actingAs(makeSecureDownloadUser('kasir'))
            ->get('/admin/import-template/download')
            ->assertOk()
            ->assertDownload('template_import_produk.xlsx');
    } finally {
        File::delete($templatePath);
    }
});

it('forbids a cashier from downloading database backups', function () {
    Storage::fake('backups');
    Storage::disk('backups')->put('database.zip', 'backup');

    $this->actingAs(makeSecureDownloadUser('kasir'))
        ->get('/admin/backups/download/database.zip')
        ->assertForbidden();
});

it('allows an authorized user to download a database backup', function () {
    Storage::fake('backups');
    Storage::disk('backups')->put('database.zip', 'backup');

    $this->actingAs(makeSecureDownloadUser('admin'))
        ->get('/admin/backups/download/database.zip')
        ->assertOk()
        ->assertDownload('database.zip');
});

it('rejects backup path traversal attempts without downloading a file', function (string $file) {
    Storage::fake('backups');
    Storage::disk('backups')->put('.env', 'secret');

    $response = $this->actingAs(makeSecureDownloadUser('admin'))
        ->get('/admin/backups/download/'.$file);

    $response->assertNotFound();
    expect($response->headers->get('Content-Disposition'))->toBeNull();
})->with(['..%2F.env', '../../.env']);
