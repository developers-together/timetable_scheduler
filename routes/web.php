<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

use App\Http\Controllers\TestController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TimetableFastController;
use App\Http\Controllers\DBLoaderController;


// Page 1 as default landing
Route::get('/', fn () => Inertia::render('generate'))->name('home');

// If anything hits /timetable-test, push to page 1 so flow is correct.
Route::redirect('/timetable-test', '/generate');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

// Route::get('/test', [TestController::class, 'index']);

require __DIR__ . '/settings.php';

<<<<<<< Updated upstream

Route::get('/generate', [TimetableController::class, 'index']);

// Route::get('/generate-timetablefast', [TimetableFastController::class, 'generateTimetable']);

Route::get('/dbload', [DBLoaderController::class, 'import']);

Route::get('/dbinput', [DBLoaderController::class, 'importInput']);
=======
// API (as-is)
Route::get('/generate-timetable', [TimetableController::class, 'generateTimetable']);
Route::get('/getAssignment', [TimetableController::class, 'getAssignment']);

// Flow: 1 → 2 → 3
Route::get('/generate', fn () => Inertia::render('generate'));  // page 1
Route::get('/waiting', fn () => Inertia::render('waiting'));    // page 2
Route::get('/timetable', fn () => Inertia::render('timetable'));// page 3
>>>>>>> Stashed changes
