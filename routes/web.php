<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

use App\Http\Controllers\TestController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TimetableFastController;
use App\Http\Controllers\DBLoaderController;



Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

// Route::get('/test', [TestController::class, 'index']);

require __DIR__ . '/settings.php';


Route::get('/generate', [TimetableController::class, 'index']);

// Route::get('/generate-timetablefast', [TimetableFastController::class, 'generateTimetable']);

Route::get('/dbload', [DBLoaderController::class, 'import']);

Route::get('/dbinput', [DBLoaderController::class, 'importInput']);
