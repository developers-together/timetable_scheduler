<?php

namespace App\Providers\CSP;

use Illuminate\Support\ServiceProvider;

use App\Models\Course;
use App\Models\CourseComponent;
use App\Models\Instructor;
use App\Models\Room;
use App\Models\TimeSlot;
use App\Models\Schedule;
use App\Models\RequiredCourse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VariableManagerProvider extends ServiceProvider
{

    private $variables = [];

    private $domain = [];

    private $neighbors = [];


    public function __construct()
    {
        $this->makeVariables();
        $this->makeDomain();
        $this->makeNeighbors();
    }

    public function getVariables()
    {
        return $this->variables;
    }
    public function getDomains()
    {
        return $this->domain;
    }
    public function getNeighbors()
    {
        return $this->neighbors;
    }

    private function makeVariables()
    {
        Log::info("Making Variables...");

        // Load all required courses into a keyed collection for quick lookup
        $requiredCourses = RequiredCourse::all()->keyBy('course_id');
        Log::info("Required Courses Count: " . $requiredCourses->count());

        // Load courses with related instructors, roles, components, and requiredCourse
        $courses = Course::with(['instructors.roles', 'components', 'requiredCourse'])->get();
        Log::info("Courses Count: " . $courses->count());

        foreach ($courses as $course) {

            // Select the instructor properly using Eloquent, not collection filters
            if ($course->type === 'Lecture') {
                $instruct = $course->instructors()
                    ->whereHas('roles', fn($q) => $q->where('role', 'prof'))
                    ->first();
            } else {
                $instruct = $course->instructors()
                    ->whereHas('roles', fn($q) => $q->where('role', 'lab_instructor'))
                    ->first();
            }

            // Handle missing instructor
            if (!$instruct) {
                Log::warning("No instructor found for course ID {$course->id} ({$course->type})");
                continue; // skip this course
            }

            // Get required capacity safely
            $capacity = $requiredCourses[$course->id]->required_capacity ?? 0;

            if ($capacity <= 0) {
                Log::warning("No capacity found for course ID {$course->id}");
                continue;
            }

            $groupCount = ceil($capacity / 90);
            $sectionCount = ceil($capacity / 30);

            foreach ($course->components as $component) {
                for ($i = 0; $i < $groupCount; $i++) {
                    for ($j = 0; $j < $sectionCount; $j++) {
                        Log::info("Processing Course {$course->id}");

                        $this->variables[] = [
                            'course_id' => $course->id, // use ->id instead of ->course_id
                            'type' => $component->type,
                            'groupNO' => $i,
                            'sectionNO' => $j,
                            'instructor_id' => $instruct->id ? $instruct->id : null,
                        ];
                    }
                }
            }
        }
    }




    private function makeDomain()
    {
        Log::info("Making Domain...");

        // $requiredCourses = RequiredCourse::all();

        // $courses = Course::where('id', '=', $requiredCourses->course_id)->get();

        $timeSlots = TimeSlot::all();

        foreach ($this->variables as $variable) {

            $rooms = [];



            if ($variable['type'] == 'Lecture') {
                // Filter rooms suitable for lectures
                $rooms = Room::where('capacity', '>', '25')->get();
            } elseif ($variable['type'] == 'Lab') {
                // Filter rooms suitable for labs

                $rooms = Room::whereIn('type', ['ComputerLab', 'BioLab', 'DrawingStudio', 'PhysicsLab', 'DrawingLab'])->get();
            } else {
                // For Tutorials, all room types are acceptable
                $rooms = Room::where('type', '<', '50')->where('type', '=', 'Classroom')->get();
            }

            foreach ($rooms as $room) {
                foreach ($timeSlots as $timeSlot) {
                    $this->domain[$variable['course_id']][] = [
                        // 'course_id' => $variable['course_id'],
                        // 'type' => $variable['type'],
                        'room_id' => $room->id,
                        'time_slot_id' => $timeSlot->id,
                    ];
                }
            }
        }
    }

    private function makeNeighbors()
    {
        Log::info("Making Neighbors...");

        foreach ($this->variables as $var) {
            $this->neighbors[$var['course_id']] = [];
        }

        for ($i = 0; $i < count($this->variables); $i++) {
            for ($j = 0; $j < count($this->variables); $j++) {
                $a = $this->variables[$i];
                $b = $this->variables[$j];

                if ($a['course_id'] != $b['course_id']) {
                    $this->neighbors[$a['course_id']][] = $b['course_id'];
                }
            }
        }
    }

    /**
     * Register services.
     */
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
