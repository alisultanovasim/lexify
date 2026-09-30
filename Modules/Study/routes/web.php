<?php
use Illuminate\Support\Facades\Route;
use Modules\Study\Http\Controllers\StudyController;
use Modules\Study\Http\Controllers\UniversalStudyController;

Route::middleware(['auth'])->group(function () {
    Route::get('/decks/{deck}/study/{mode?}', [StudyController::class, 'show'])->name('study.show');
    Route::post('/study/{session}/answer', [StudyController::class, 'answer'])->name('study.answer');
    Route::post('/study/{session}/complete', [StudyController::class, 'complete'])->name('study.complete');

    Route::get('/universal-study/decks', [UniversalStudyController::class, 'decks'])->name('universal.study.decks');
    Route::post('/universal-study/start', [UniversalStudyController::class, 'start'])->name('universal.study.start');
    Route::get('/universal-study/session/{session}', [UniversalStudyController::class, 'session'])->name('universal.study.session');
});
