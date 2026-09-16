<?php

use App\Http\Controllers\PaymentController;
use App\Http\Middleware\VerifyMayarWebhook;
use App\Models\Backup;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return redirect('/admin/login');
});

Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

// Backup download route
Route::get('/admin/backups/download/{file}', function ($file) {
    Gate::authorize('viewAny', Backup::class);

    $disk = Storage::disk('backups');

    if (! in_array($file, $disk->allFiles(), true) || ! str_ends_with($file, '.zip')) {
        abort(404);
    }

    return $disk->download($file, basename($file));
})->where('file', '.*')->name('backup.download')->middleware('auth');

// Product import template download route
Route::get('/admin/import-template/download', function () {
    $path = storage_path('app/public/templates/product_import_template.xlsx');

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->download($path, 'template_import_produk.xlsx');
})->name('import-template.download')->middleware('auth');

// Payment Gateway Webhook Routes
Route::post('/webhook/mayar/{token}', [PaymentController::class, 'handleMayarWebhook'])
    ->name('payment.webhook.mayar')
    ->middleware([VerifyMayarWebhook::class, 'throttle:60,1']);

// Payment Gateway API Routes
Route::prefix('payment')->group(function () {
    Route::get('/callback/{provider}', [PaymentController::class, 'handleCallback'])
        ->name('payment.callback');
});
