<?php
use Illuminate\Support\Facades\Route;
use Modules\Vocabulary\Http\Controllers\TermController;

Route::middleware(['auth'])->group(function () {
    Route::get('/decks/{deck}/terms/create', [TermController::class, 'create'])->name('terms.create');
    Route::post('/decks/{deck}/terms', [TermController::class, 'store'])->name('terms.store');
    Route::put('/terms/{term}', [TermController::class, 'update'])->name('terms.update');
    Route::delete('/terms/{term}', [TermController::class, 'destroy'])->name('terms.destroy');
    Route::post('/decks/{deck}/terms/reorder', [TermController::class, 'reorder'])->name('terms.reorder');
    Route::post('/terms/{term}/enrich', [TermController::class, 'enrich'])->name('terms.enrich');
    Route::post('/decks/{deck}/terms/enrich-last', [TermController::class, 'enrichLast'])->name('terms.enrichLast');
    Route::get('/decks/{deck}/terms/last', [TermController::class, 'lastTerm'])->name('terms.last');
});
