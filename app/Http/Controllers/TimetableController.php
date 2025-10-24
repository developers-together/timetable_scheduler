<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Providers\CSP\CspSchedulerProvider as CspScheduler;


class TimetableController extends Controller
{

    public function index()
    {
        $scheduler = new CspScheduler();
        $result = $scheduler->generateSchedule();

        dd($result); // prints timetable + score
    }
}
