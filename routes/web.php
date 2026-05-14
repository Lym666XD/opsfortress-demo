<?php

use App\Http\Controllers\Admin\WorkplaceController;
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

    /*
     * Admin routes — M10 Slice 1.
     *
     * Authorization is per-controller-action via Policies (see
     * WorkplacePolicy). Multi-tenant scoping is automatic via the
     * BelongsToTenant global scope on the Workplace and Business models.
     */
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('workplaces', [WorkplaceController::class, 'index'])->name('workplaces.index');
        Route::get('workplaces/create', [WorkplaceController::class, 'create'])->name('workplaces.create');
        Route::post('workplaces', [WorkplaceController::class, 'store'])->name('workplaces.store');
    });
});

require __DIR__.'/settings.php';
