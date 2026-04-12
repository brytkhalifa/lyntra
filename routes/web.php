<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ShortLinks\RedirectShortLinkController;
use App\Http\Controllers\ShortLinks\ResolveShortUrlController;
use App\Http\Controllers\ShortLinks\ShortLinkController;
use App\Http\Controllers\ShortLinks\ShortLinkQrCodeController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('links/expand', [ShortLinkController::class, 'expand'])
        ->name('links.expand');

    Route::post('links/expand', ResolveShortUrlController::class)
        ->name('links.expand.submit')
        ->middleware('throttle:30,1');

    Route::get('links/{link}/qr', ShortLinkQrCodeController::class)
        ->name('links.qr')
        ->middleware('throttle:60,1');

    Route::resource('links', ShortLinkController::class);
});

require __DIR__.'/settings.php';

Route::get('{slug}', RedirectShortLinkController::class)
    ->middleware('throttle:120,1')
    ->name('short-link.redirect');
