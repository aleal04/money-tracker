<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'index'])->name('home');

require __DIR__.'/periods.php';

Route::fallback(fn () => abort(404));
