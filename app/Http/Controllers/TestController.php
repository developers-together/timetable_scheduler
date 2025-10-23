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

    public function index()
    {
        $courseids = array(
            'CSC111',
            'ECE111',
            'LRA101',
            'LRA103',
            'LRA104',
            'LRA105',
            'LRA401',
            'MTH111',
            'PHY113',
            'CNC111',
            'CSC211',
            'CSE214',
            'LRA306',
            'LRA403',
            'MTH212',
            'ACM215',
            'CSC114'
        );

        $slots = [
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

        $courses = Course::with('qualifiedInstructors')->whereIn('course_id', $courseids)->get();
        $allRooms = Room::all();

        $domain = [];

        foreach ($courses as $course) {
            $types = explode(',', $course->course_type);
            $qualified = $course->qualifiedInstructors;

            foreach ($types as $type) {
                $domain[$course->course_id][$type] = [];

                // Determine rooms and instructors based on type
                // Since capacity is null, we'll use room_type to filter
                if ($type === 'Lecture') {
                    // Use all Classroom type rooms for lectures
                    $rooms = $allRooms->filter(fn($r) => $r->room_type === 'Classroom');
                    $instructors = $qualified->filter(fn($i) => str_starts_with($i->instructor_id, 'PROF'));
                } elseif ($type === 'Tutorial') {
                    // Use Classroom rooms for tutorials too (smaller subset if available)
                    $rooms = $allRooms->filter(fn($r) => $r->room_type === 'Classroom');
                    $instructors = $qualified->filter(fn($i) => str_starts_with($i->instructor_id, 'AP'));
                } else { // Lab
                    $rooms = $allRooms->filter(fn($r) => str_contains($r->room_type, 'Lab'));
                    $instructors = $qualified->filter(fn($i) => str_starts_with($i->instructor_id, 'AP'));
                }

                // Build domain values
                foreach ($slots as $slot) {
                    foreach ($rooms as $room) {
                        if ($instructors->isNotEmpty()) {
                            foreach ($instructors as $instructor) {
                                $domain[$course->course_id][$type][] = [
                                    'slot' => $slot,
                                    'room_id' => $room->room_id,
                                    'instructor_id' => $instructor->instructor_id,
                                ];
                            }
                        } else {
                            $domain[$course->course_id][$type][] = [
                                'slot' => $slot,
                                'room_id' => $room->room_id,
                                'instructor_id' => null
                            ];
                        }
                    }
                }
            }
        }

        dd($domain);
    }


    // public function index()
    // {
    //     $allRooms = Room::all();

    //     dd([
    //         'total_rooms' => $allRooms->count(),
    //         'sample_rooms' => $allRooms->take(5)->map(function($room) {
    //             return [
    //                 'room_id' => $room->room_id,
    //                 'capacity' => $room->capacity,
    //                 'capacity_type' => gettype($room->capacity),
    //                 'capacity_is_null' => is_null($room->capacity),
    //                 'room_type' => $room->room_type,
    //             ];
    //         })->toArray(),
    //         'null_capacities' => $allRooms->where('capacity', null)->count(),
    //         'non_null_capacities' => $allRooms->whereNotNull('capacity')->count(),
    //     ]);
    // }
};
