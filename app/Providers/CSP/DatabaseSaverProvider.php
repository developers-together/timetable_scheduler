<?php

namespace App\Providers\CSP;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use App\Models\Schedule;

use App\Database\Models\RequiredCourse;
use App\Models\RequiredCourse as ModelsRequiredCourse;
use Symfony\Contracts\Service\Attribute\Required;

class DatabaseSaverProvider extends ServiceProvider
{
    /**
     * Register services.
     */

    public function __construct() {}

    public function saveOnDB(array $assignment, int $score, array $variables)
    {
        foreach ($assignment as $var => $details) {

            $reqcourse = ModelsRequiredCourse::where('course_id', $variables[$var]['course_id'])->first();

            Schedule::create([
                'level' => $reqcourse->level,
                'term' => $reqcourse->term,
                'faculty' => $reqcourse->faculty,
                'slot' => 'full',
                'course_id' => $reqcourse->course_id,
                'course_component_id' => $variables[$var]['type'],
                'instructor_id' => $variables[$var]['instructor_id'],
                'room_id' => $details['room_id'],
                'time_slot_id' => $details['time_slot_id'],
                'groupNO' => $variables[$var]['groupNO'],
                'sectionNO' => $variables[$var]['sectionNO']
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
