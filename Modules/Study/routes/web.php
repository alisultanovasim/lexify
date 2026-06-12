<?php
use Illuminate\Support\Facades\Route;
use Modules\Study\Http\Controllers\StudyController;

Route::middleware(['auth'])->group(function () {
    Route::get('/decks/{deck}/study/{mode?}', [StudyController::class, 'show'])->name('study.show');
    Route::post('/study/{session}/answer', [StudyController::class, 'answer'])->name('study.answer');
    Route::post('/study/{session}/complete', [StudyController::class, 'complete'])->name('study.complete');
});
