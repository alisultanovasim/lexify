<?php
use Illuminate\Support\Facades\Route;
use Modules\Deck\Http\Controllers\DeckController;

Route::middleware(['auth'])->group(function () {
    Route::resource('decks', DeckController::class);
    Route::get('/explore', [DeckController::class, 'explore'])->name('decks.explore');
    Route::post('/decks/{deck}/clone', [DeckController::class, 'clone'])->name('decks.clone');
    Route::get('/decks/{deck}/terms', [DeckController::class, 'terms'])->name('decks.terms');
});
