<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Space\SpaceController;

Route::group(['prefix' => 'spaces', 'as' => 'spaces.'], function () {
    Route::get('/', [SpaceController::class, 'index'])->name('index');
    Route::get('/get-space-list', [SpaceController::class, 'spaceList'])->name('space-list');
    Route::post('/store', [SpaceController::class, 'store'])->name('store');
    Route::put('/{spaceId}/update', [SpaceController::class, 'update'])->whereNumber('spaceId')->name('update');
    Route::delete('/{spaceId}/delete', [SpaceController::class, 'destroy'])->whereNumber('spaceId')->name('destroy');
    Route::post('/{spaceId}/make-primary', [SpaceController::class, 'makePrimary'])->whereNumber('spaceId')->name('make-primary');
});
