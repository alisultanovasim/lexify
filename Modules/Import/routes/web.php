<?php
use Illuminate\Support\Facades\Route;
use Modules\Import\Http\Controllers\ImportController;

Route::middleware(['auth'])->group(function () {
    Route::get('/decks/{deck}/import', [ImportController::class, 'show'])->name('import.show');
    Route::post('/decks/{deck}/import', [ImportController::class, 'store'])->name('import.store');
});
