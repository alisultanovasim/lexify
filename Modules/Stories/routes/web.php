<?php
use Illuminate\Support\Facades\Route;
use Modules\Stories\Http\Controllers\StoryController;

Route::middleware(['auth'])->group(function () {
    Route::resource('stories', StoryController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::post('/stories/{story}/lookup-word',   [StoryController::class, 'lookupWord'])->name('stories.lookup-word');
    Route::post('/stories/{story}/translate-word', [StoryController::class, 'translateWord'])->name('stories.translate-word');
    Route::post('/stories/{story}/add-word',       [StoryController::class, 'addWord'])->name('stories.add-word');
});
