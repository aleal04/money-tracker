<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Template\TemplateController;

Route::group(['prefix' => 'templates', 'as' => 'templates.'], function () {
    Route::get('/', [TemplateController::class, 'index'])->name('index');
    Route::get('/get-template-list', [TemplateController::class, 'templateList'])->name('template-list');
    Route::post('/store', [TemplateController::class, 'store'])->name('store');
    Route::put('/{templateId}/update', [TemplateController::class, 'update'])->whereNumber('templateId')->name('update');
    Route::delete('/{templateId}/delete', [TemplateController::class, 'destroy'])->whereNumber('templateId')->name('destroy');
});
