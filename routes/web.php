<?php

use App\Http\Controllers\ShortLinks\RedirectShortLinkController;
use App\Http\Controllers\ShortLinks\ShortLinkController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('links', ShortLinkController::class);
});

require __DIR__.'/settings.php';

Route::get('{slug}', RedirectShortLinkController::class)
    ->middleware('throttle:120,1')
    ->name('short-link.redirect');
