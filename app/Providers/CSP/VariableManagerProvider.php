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
        Log::debug("Variables Created: " . count($this->variables));
        Log::debug($this->variables[0]);


        $this->makeDomain();
        Log::debug("Domains Created." . count($this->domain));
        Log::debug($this->domain[0][0]);


        $this->makeNeighbors();
        Log::debug("Neighbors Created." . count($this->neighbors));
        Log::debug($this->neighbors[0]);

        Log::warning("Total variables: " . count($this->domain));
        Log::warning("Average domain size: " . array_sum(array_map('count', $this->domain)) / count($this->domain));
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
        $requiredCourses = RequiredCourse::all();
        Log::info("Required Courses Count: " . $requiredCourses->count());

        // $courseIds = $requiredCourses->pluck('course_id');

        // Load courses with related instructors, roles, components, and requiredCourse
        // $courses = Course::with(['instructors.roles', 'components', 'requiredCourse'])->get();
        // Log::info("Courses Count: " . $courses->count());

        // $courses = $requiredCourses->courses();



        foreach ($requiredCourses as $requiredCourse) {

            $course = $requiredCourse->course;

            // Log::debug("Processing Course ID: {$course->id}");

            // Select the instructor properly using Eloquent, not collection filters
            if ($course->type === 'Lecture') {
                $instructor = $course->instructors()
                    ->whereHas('roles', fn($q) => $q->where('role', 'prof'))
                    ->first();
            } else {
                $instructor = $course->instructors()
                    ->whereHas('roles', fn($q) => $q->where('role', '!=', 'prof'))
                    ->first();
            }

            if (!$instructor) {
                Log::warning("No instructor found for course ID {$course->id} ");
            }

            $capacity = $requiredCourse->required_capacity;

            if ($capacity <= 0) {
                Log::warning("No capacity found for course ID {$course->id}");
                continue;
            }

            $groupCount = ceil($capacity / 90);
            $sectionCount = ceil($capacity / 30);

            foreach ($course->components as $component) {

                $count = ($component->type === 'Lecture') ? $groupCount : $sectionCount;

                for ($i = 1; $i <= $count; $i++) {
                    Log::info("Processing Course {$course->id}");

                    if (is_null($instructor)) {
                        $ins = null;
                    } else {
                        $ins = $instructor->id;
                    }
                    $this->variables[] = [
                        'course_id' => $course->id,
                        'type' => $component->type,
                        'groupNO' => $i,
                        'sectionNO' => ($component->type === 'Lecture') ? 0 : $i,
                        'instructor_id' => $ins
                    ];
                }
            }
        }
    }





    private function makeDomain()
    {
        Log::info("Making Domain...");

        $timeSlots = TimeSlot::all();

        foreach ($this->variables as $var => $variable) {

            $rooms = [];

            $this->domain[$var] = [];

            if ($variable['type'] == 'Lecture') {
                // Filter rooms suitable for lectures
                $rooms = Room::whereIn('type', ['Classroom', 'Theater', 'Hall'])->where('capacity', '>', '25')->get();
            } elseif ($variable['type'] == 'Lab') {
                // Filter rooms suitable for labs

                $rooms = Room::where(function ($query) {
                    $query->where('type', 'LIKE', '%Lab%')
                        ->orWhere('type', 'LIKE', '%Studio%')
                        ->orWhereIn('type', ['ComputerLab', 'BioLab', 'DrawingStudio', 'PhysicsLab', 'DrawingLab', 'Classroom']);
                })->get();
            } else {
                // For Tutorials, all room types are acceptable
                $rooms = Room::where('capacity', '<', '50')->where('type', '=', 'Classroom')->get();
            }

            foreach ($rooms as $room) {
                foreach ($timeSlots as $timeSlot) {
                    $this->domain[$var][] = [
                        // 'course_id' => $variable['course_id'],
                        // 'type' => $variable['type'],
                        'room_id' => $room->id,
                        'time_slot_id' => $timeSlot->id,
                        // 'instructor_id' => $variable['instructor_id']
                    ];
                }
            }

            if (empty($this->domain[$var])) {
                Log::error("Variable {$var} has EMPTY domain! Type: {$variable['type']}");
            }
        }
    }

    private function makeNeighbors()
    {
        Log::info("Making Neighbors...");

        $count = count($this->variables);

        // Initialize neighbors as empty arrays
        for ($i = 0; $i < $count; $i++) {
            $this->neighbors[$i] = [];
        }

        // Build neighbor relationships
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $this->variables[$i];
                $b = $this->variables[$j];

                $shareInstructor = !is_null($a['instructor_id'])
                    && !is_null($b['instructor_id'])
                    && $a['instructor_id'] == $b['instructor_id'];

                $sameRoom = true;

                if ($shareInstructor || $sameRoom) {
                    $this->neighbors[$i][] = $j;
                    $this->neighbors[$j][] = $i; // make it symmetric
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
