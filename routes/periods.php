<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Period\LineController;
use App\Http\Controllers\Period\PeriodController;

Route::group(['prefix' => 'periods', 'as' => 'periods.'], function () {
    Route::get('/', [PeriodController::class, 'index'])->name('index');
    Route::post('/store', [PeriodController::class, 'store'])->name('store');
    Route::post('/next', [PeriodController::class, 'storeNext'])->name('next');
    Route::get('/{periodId}', [PeriodController::class, 'show'])->whereNumber('periodId')->name('show');
    Route::get('/{periodId}/data', [PeriodController::class, 'data'])->whereNumber('periodId')->name('data');
    Route::get('/{periodId}/close-preview', [PeriodController::class, 'closePreview'])->whereNumber('periodId')->name('close-preview');
    Route::post('/{periodId}/close', [PeriodController::class, 'close'])->whereNumber('periodId')->name('close');
    Route::post('/{periodId}/reopen', [PeriodController::class, 'reopen'])->whereNumber('periodId')->name('reopen');
    Route::post('/{periodId}/lines/store', [LineController::class, 'store'])->whereNumber('periodId')->name('lines.store');
});

Route::group(['prefix' => 'lines', 'as' => 'lines.'], function () {
    Route::put('/{lineId}/update', [LineController::class, 'update'])->whereNumber('lineId')->name('update');
    Route::post('/{lineId}/toggle-paid', [LineController::class, 'togglePaid'])->whereNumber('lineId')->name('toggle-paid');
    Route::delete('/{lineId}/delete', [LineController::class, 'destroy'])->whereNumber('lineId')->name('destroy');
    Route::get('/{lineId}/arrastre-detail', [LineController::class, 'arrastreDetail'])->whereNumber('lineId')->name('arrastre-detail');
});
