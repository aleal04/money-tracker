<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');

require __DIR__.'/periods.php';
require __DIR__.'/installments.php';
require __DIR__.'/templates.php';
require __DIR__.'/spaces.php';

Route::fallback(fn () => abort(404));
