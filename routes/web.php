<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

use App\Http\Controllers\TestController;
use App\Http\Controllers\TimetableController;


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

Route::get('/test', [TestController::class, 'index']);

require __DIR__ . '/settings.php';


Route::get('/generate-timetable', [TimetableController::class, 'generateTimetable']);

Route::get('/getAssignment', [TimetableController::class, 'getAssignment']);
