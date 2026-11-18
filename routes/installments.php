<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Installment\InstallmentController;

Route::post('/cards/store', [InstallmentController::class, 'storeCard'])->name('cards.store');

Route::group(['prefix' => 'installments', 'as' => 'installments.'], function () {
    Route::get('/', [InstallmentController::class, 'index'])->name('index');
    Route::get('/get-card-list', [InstallmentController::class, 'cardList'])->name('card-list');
    Route::post('/store', [InstallmentController::class, 'store'])->name('store');
    Route::put('/{installmentId}/update', [InstallmentController::class, 'update'])->whereNumber('installmentId')->name('update');
    Route::delete('/{installmentId}/delete', [InstallmentController::class, 'destroy'])->whereNumber('installmentId')->name('destroy');
    Route::post('/{installmentId}/toggle-cancel-soon', [InstallmentController::class, 'toggleCancelSoon'])->whereNumber('installmentId')->name('toggle-cancel-soon');
});
