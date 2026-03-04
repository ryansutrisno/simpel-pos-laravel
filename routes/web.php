<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return redirect('/admin/login');
});

// Backup download route
Route::get('/admin/backups/download/{file}', function ($file) {
    $path = $file;

    if (! Storage::disk('backups')->exists($path)) {
        abort(404);
    }

    return Storage::disk('backups')->download($path);
})->name('backup.download')->middleware('auth');

// Product import template download route
Route::get('/admin/import-template/download', function () {
    $path = storage_path('app/public/templates/product_import_template.xlsx');

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->download($path, 'template_import_produk.xlsx');
})->name('import-template.download');
