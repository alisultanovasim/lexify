<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Redirect root to dashboard or login
Route::get('/', fn () => redirect()->route('dashboard'));

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login',    [LoginController::class,    'create'])->name('login');
    Route::post('/login',   [LoginController::class,    'store']);
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register',[RegisterController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Password reset placeholder (opsional, sonra əlavə olunar)
Route::get('/forgot-password', fn () => Inertia::render('Auth/Login'))
    ->name('password.request');

// Dashboard + Profile
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        $userId = auth()->id();
        return \Inertia\Inertia::render('Dashboard', [
            'totalDecks'  => \Modules\Deck\Models\Deck::where('user_id', $userId)->count(),
            'totalTerms'  => \Modules\Vocabulary\Models\Term::whereHas('deck', fn ($q) => $q->where('user_id', $userId))->count(),
            'dueCount'    => \Modules\Progress\Models\UserTermProgress::where('user_id', $userId)->where('next_review_at', '<=', now())->count(),
            'recentDecks' => \Modules\Deck\Models\Deck::where('user_id', $userId)->withCount('terms')->latest()->take(3)->get(),
        ]);
    })->name('dashboard');

    // Profile
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');
});
