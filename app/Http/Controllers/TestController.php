<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\Room;
use App\Models\Timetable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\instance;

class TestController extends Controller
{
    public  $slots = [
        'Sunday-09:00-10:30',
        'Sunday-10:45-12:15',
        'Sunday-12:30-14:00',
        'Sunday-14:15-15:45',
        'Monday-09:00-10:30',
        'Monday-10:45-12:15',
        'Monday-12:30-14:00',
        'Monday-14:15-15:45',
        'Tuesday-09:00-10:30',
        'Tuesday-10:45-12:15',
        'Tuesday-12:30-14:00',
        'Tuesday-14:15-15:45',
        'Wednesday-09:00-10:30',
        'Wednesday-10:45-12:15',
        'Wednesday-12:30-14:00',
        'Wednesday-14:15-15:45',
        'Thursday-09:00-10:30',
        'Thursday-10:45-12:15',
        'Thursday-12:30-14:00',
        'Thursday-14:15-15:45',
    ];

    public function index() {}

    protected function domains($courseids)
    {

        $courses = Course::with('qualifiedInstructors')->whereIn('course_id', $courseids)->get();
        $rooms = Room::all();
        $instructor = instructor::all();
        $domain = [];


        /*
            domain[courseid][type][time][room][instructor]

            domain[esc111][lec][mon-09:00-10:30][bB.G.01]
        */
        foreach ($courses as $course) {

            $types = explode(',', $course->course_type);

            foreach ($types as $type) {
                $domain[$course->course_id][$type];
            }



            $qualified = $course->qualifiedInstructors()->where('course_id', $course->course_id);

            foreach (array_keys($domain[$course->course_id]) as $key) {

                if ($key == 'Lecture') {
                } else if ($key == 'Tutorial') {
                } else {
                }
            }

            if ($qualified->isEmpty()) {
                $qualified = collect([null]);
            }

            if ($course->course_type == 'Lecture')
                $avrooms = $rooms->where('capacity', '>', '25'); //->where('capacity','<','100');



        }
    }
}
