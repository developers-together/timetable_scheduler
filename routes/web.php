<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

use App\Http\Controllers\TimetableController;
use App\Http\Controllers\DBLoaderController;
use App\Http\Controllers\GenerateInputController;



// Page 1 as default landing
Route::get('/', [GenerateInputController::class, 'index']);

// If anything hits /timetable-test, push to page 1 so flow is correct.
// Route::redirect('/timetable-test', '/generate');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

// Route::get('/test', [TestController::class, 'index']);

require __DIR__ . '/settings.php';





