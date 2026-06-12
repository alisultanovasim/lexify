<?php
use Illuminate\Support\Facades\Route;
use Modules\Image\Http\Controllers\ImageController;

Route::middleware(['auth'])->group(function () {
    Route::get('/image-search', [ImageController::class, 'search'])->name('image.search');
    Route::post('/terms/{term}/image', [ImageController::class, 'save'])->name('image.save');
    Route::delete('/images/{image}', [ImageController::class, 'destroy'])->name('image.destroy');
});
