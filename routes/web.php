<?php

use App\Http\Controllers\OrganizerController;
use App\Http\Controllers\RouletteController;
use App\Http\Middleware\EnsureOrganizer;
use Illuminate\Support\Facades\Route;

// Players
Route::get('/', [RouletteController::class, 'index'])->name('roulette');
Route::middleware('throttle:30,1')->group(function () {
    Route::post('/check', [RouletteController::class, 'check'])->name('roulette.check');
    Route::post('/spin', [RouletteController::class, 'spin'])->name('roulette.spin');
});

// Organizer (not linked from the players' page; open /admin directly)
Route::get('/admin/login', [OrganizerController::class, 'showLogin'])->name('organizer.login');
Route::post('/admin/login', [OrganizerController::class, 'login'])->middleware('throttle:5,1')->name('organizer.login.submit');

Route::middleware(EnsureOrganizer::class)->prefix('admin')->name('organizer.')->group(function () {
    Route::get('/', [OrganizerController::class, 'index'])->name('index');
    Route::post('/participants', [OrganizerController::class, 'store'])->name('participants.store');
    Route::patch('/participants/{participant}', [OrganizerController::class, 'update'])->name('participants.update');
    Route::patch('/participants/{participant}/group', [OrganizerController::class, 'switchGroup'])->name('participants.group');
    Route::delete('/participants/{participant}', [OrganizerController::class, 'destroy'])->name('participants.destroy');
    Route::post('/reset', [OrganizerController::class, 'reset'])->name('reset');
    Route::post('/logout', [OrganizerController::class, 'logout'])->name('logout');
});
