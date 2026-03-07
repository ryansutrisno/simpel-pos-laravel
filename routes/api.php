<?php

use App\Http\Controllers\Api\ReturnController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('api.transactions.show');
    Route::get('/returns/{id}', [ReturnController::class, 'show']);
    Route::get('/returns/{id}/receipt', [ReturnController::class, 'receipt']);

    // Payment Gateway API Routes
    Route::post('/payments/{transaction}/initiate', [PaymentController::class, 'initiatePayment'])
        ->name('api.payments.initiate');
    Route::get('/payments/{transaction}/status', [PaymentController::class, 'checkStatus'])
        ->name('api.payments.status');
});
