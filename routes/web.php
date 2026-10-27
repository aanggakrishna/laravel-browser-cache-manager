<?php

use Aanggakrishna\BrowserCacheManager\Http\Controllers\AdminController;
use Aanggakrishna\BrowserCacheManager\Http\Controllers\RecoveryController;
use Aanggakrishna\BrowserCacheManager\Http\Controllers\StatusController;
use Illuminate\Support\Facades\Route;

$prefix = trim((string) config('browser-cache-manager.route_prefix', 'browser-cache'), '/');
Route::get($prefix.'/status', StatusController::class)->name('browser-cache-manager.status')->middleware('throttle:120,1');
Route::post($prefix.'/recover', RecoveryController::class)->name('browser-cache-manager.recover')->middleware(['web', 'throttle:15,1']);

if (config('browser-cache-manager.admin.enabled', false)) {
    Route::middleware(config('browser-cache-manager.admin.middleware', ['web', 'auth', 'can:manage-browser-cache']))
        ->prefix($prefix.'/admin')->name('browser-cache-manager.admin.')
        ->group(function (): void {
            Route::get('/', [AdminController::class, 'index'])->name('index');
            Route::put('/', [AdminController::class, 'update'])->name('update');
        });
}
