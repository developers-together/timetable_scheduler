<?php

namespace App\Providers\CSP;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use App\Models\Schedule;


class DatabaseSaverProvider extends ServiceProvider
{
    /**
     * Register services.
     */

    public function __construct() {}

    public function saveOnDB(array $assignment, int $score)
    {
        foreach ($assignment as $course_id => $details) {

            Schedule::create([
                'level' => $details->level,
                'term' => $details->term,
                'faculty' => $details->faculty,
                'slot' => $details->slot,
                'course_id' => $course_id,
                'course_component_id' => $details['course_component_id'],
                'instructor_id' => $details['instructor_id'],
                'room_id' => $details['room_id'],
                'time_slot_id' => $details['time_slot_id'],
                'groupNO' => $details['groupNO'],
                'sectionNO' => $details['sectionNO']
            ]);
        }
    }

    public function resetDB()
    {
        DB::table('schedules')->truncate();
    }
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
