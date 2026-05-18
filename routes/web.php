<?php

use Illuminate\Support\Facades\Route;

// Public registration was disabled in Phase 0 (see config/fortify.php and
// MILESTONE.md P0-7). The 'canRegister' prop is no longer threaded through
// the Welcome page.
Route::inertia('/', 'welcome')->name('home');

Route::inertia('preview', 'preview/home')->name('preview.home');
Route::inertia('preview/{slug}', 'preview/placeholder')
    ->where('slug', '[a-z0-9-]+')
    ->name('preview.placeholder');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
