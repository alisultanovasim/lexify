<?php
use Illuminate\Support\Facades\Route;
use Modules\Progress\Http\Controllers\ProgressController;

Route::middleware(['auth'])->group(function () {
    Route::get('/progress', [ProgressController::class, 'index'])->name('progress.index');
});
