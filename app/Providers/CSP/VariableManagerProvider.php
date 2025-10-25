<?php

namespace App\Providers\CSP;

use Illuminate\Support\ServiceProvider;
use App\Models\Course;
use App\Models\Room;
use App\Models\TimeSlot;
use App\Models\RequiredCourse;
use Illuminate\Support\Facades\Log;

class VariableManagerProvider extends ServiceProvider
{
    private array $variables = [];
    private array $domains = [];
    private array $neighbors = [];

    private array $instructorsUsed = [];

    // Cache for frequently accessed data
    private $timeSlots;
    private $rooms;

    // Slot configurations
    private const FULL_SLOT = 'full';
    private const FIRST_HALF = 'first_half';
    private const SECOND_HALF = 'second_half';

    public function __construct()
    {
        Log::info("=== Initializing Variable Manager (with Slot Support) ===");

        $startTime = microtime(true);

        // Load data once
        $this->timeSlots = TimeSlot::all();
        $this->rooms = Room::all();

        Log::info("Loaded {$this->timeSlots->count()} time slots and {$this->rooms->count()} rooms");

        $this->makeVariables();
        $this->makeDomains();
        $this->makeNeighbors();

        $totalTime = microtime(true) - $startTime;

        Log::info("=== Variable Manager Summary ===");
        Log::info("Total variables: " . count($this->variables));
        Log::info("Total domains: " . count($this->domains));
        Log::info("Total domain values: " . array_sum(array_map('count', $this->domains)));
        Log::info("Average domain size: " . round(array_sum(array_map('count', $this->domains)) / max(1, count($this->domains)), 2));
        Log::info("Initialization time: " . round($totalTime, 3) . "s");
        Log::info("===================================");
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function getDomains(): array
    {
        return $this->domains;
    }

    public function getNeighbors(): array
    {
        return $this->neighbors;
    }

    /**
     * Create CSP variables from required courses
     */
    private function makeVariables(): void
    {
        Log::info("Creating variables from required courses...");

        $requiredCourses = RequiredCourse::with(['course.instructors.roles', 'course.components'])->get();

        if ($requiredCourses->isEmpty()) {
            Log::warning("No required courses found!");
            return;
        }

        Log::info("Processing {$requiredCourses->count()} required courses");

        $variableCount = 0;
        $skippedCourses = 0;

        foreach ($requiredCourses as $requiredCourse) {
            $course = $requiredCourse->course;

            if (!$course) {
                Log::warning("Required course ID {$requiredCourse->id} has no associated course");
                continue;
            }

            $capacity = $requiredCourse->required_capacity;

            if ($capacity <= 0) {
                Log::warning("Course {$course->id} ({$course->name}) has invalid capacity: {$capacity}");
                $skippedCourses++;
                continue;
            }

            // Calculate number of groups/sections needed
            $groupCount = (int) ceil($capacity / 90);    // Lectures: max 90 students
            $sectionCount = (int) ceil($capacity / 30);  // Labs/Tutorials: max 30 students

            foreach ($course->components as $component) {
                // Select appropriate instructor
                $instructor = $this->selectInstructor($course, $component->type, $requiredCourse);

                if (!$instructor) {
                    Log::warning("No instructor for course {$course->id} ({$course->name}), component {$component->type}");
                }

                $count = ($component->type === 'Lecture') ? $groupCount : $sectionCount;

                // Determine slot type based on component
                $slotType = $this->getSlotTypeForComponent($component->type);

                for ($i = 1; $i <= $count; $i++) {
                    $this->variables[] = [
                        'course_id' => $course->id,
                        'course_name' => $course->name ?? 'Unknown',
                        'type' => $component->type,
                        'groupNO' => $i,
                        'sectionNO' => ($component->type === 'Lecture') ? 0 : $i,
                        'instructor_id' => $instructor?->id,
                        'instructor_name' => $instructor?->name ?? 'Unassigned',
                        'capacity' => $capacity,
                        'slot_type' => $slotType,  // NEW: slot type requirement
                    ];
                    $variableCount++;
                }
            }
        }

        Log::info("Created {$variableCount} variables ({$skippedCourses} courses skipped)");

        // Log variable type distribution
        $typeDistribution = [];
        $slotDistribution = [];

        foreach ($this->variables as $var) {
            $type = $var['type'];
            $slot = $var['slot_type'];
            $typeDistribution[$type] = ($typeDistribution[$type] ?? 0) + 1;
            $slotDistribution[$slot] = ($slotDistribution[$slot] ?? 0) + 1;
        }

        Log::info("Variable distribution by type:");
        foreach ($typeDistribution as $type => $count) {
            Log::info("  - {$type}: {$count} variables");
        }

        Log::info("Variable distribution by slot:");
        foreach ($slotDistribution as $slot => $count) {
            Log::info("  - {$slot}: {$count} variables");
        }
    }

    /**
     * Determine slot type requirement based on component type
     */
    private function getSlotTypeForComponent(string $componentType): string
    {
        switch ($componentType) {
            case 'Lecture':
            case 'Lab':
                return self::FULL_SLOT;  // Lectures and Labs take full slots

            case 'Tutorial':
                return self::FIRST_HALF;  // Tutorials take half slots (we'll use first_half as default)

            default:
                Log::warning("Unknown component type: {$componentType}, defaulting to full slot");
                return self::FULL_SLOT;
        }
    }

    /**
     * Select appropriate instructor based on component type
     */
    private function selectInstructor($course, string $componentType, $requiredCourse)
    {
        if ($componentType === 'Lecture') {

            if (!is_null($requiredCourse) && !is_null($requiredCourse->instructor_id)) {
                return $course->instructors()
                    ->where('id', $requiredCourse->instructor_id)
                    ->first();
            }
            $temp = $course->instructors()
                ->whereHas('roles', fn($q) => $q->where('role', 'prof'))->whereNotIn('id', $this->instructorsUsed)
                ->first();

            $this->instructorsUsed[] = $temp->id;

            if (is_null($temp)) {
                $temp = $course->instructors()
                    ->whereHas('roles', fn($q) => $q->where('role', 'prof'))
                    ->first();

                return $temp;

                if (is_null($temp)) {
                    return null;
                }

                return $temp;
            }
        }

        // return null;

        $temp = $course->instructors()
            ->whereHas('roles', fn($q) => $q->where('role', '!=', 'prof'))->whereNotIn('id', $this->instructorsUsed)
            ->first();

        if (is_null($temp)) {
            $temp = $course->instructors()
                ->whereHas('roles', fn($q) => $q->where('role', '!=', 'prof'))
                ->first();

            return $temp;

            if (is_null($temp)) {
                return null;
            }
        }

        $this->instructorsUsed[] = $temp->id;

        return $temp;
    }

    /**
     * Create domains for each variable
     */
    private function makeDomains(): void
    {
        Log::info("Creating domains with slot support...");

        $emptyDomains = 0;
        $domainStats = [
            'Lecture' => ['count' => 0, 'total_size' => 0],
            'Lab' => ['count' => 0, 'total_size' => 0],
            'Tutorial' => ['count' => 0, 'total_size' => 0],
        ];

        foreach ($this->variables as $varIndex => $variable) {
            $rooms = $this->getRoomsForType($variable['type']);

            if ($rooms->isEmpty()) {
                Log::error("No rooms available for variable {$varIndex} (Type: {$variable['type']}, Course: {$variable['course_name']})");
                $emptyDomains++;
                $this->domains[$varIndex] = [];
                continue;
            }

            $this->domains[$varIndex] = [];
            $slotType = $variable['slot_type'];

            foreach ($rooms as $room) {
                foreach ($this->timeSlots as $timeSlot) {
                    // Generate domain values based on slot type
                    if ($slotType === self::FULL_SLOT) {
                        // Full slot: only one domain value per room-timeslot
                        $this->domains[$varIndex][] = [
                            'room_id' => $room->id,
                            'time_slot_id' => $timeSlot->id,
                            'slot' => self::FULL_SLOT,
                        ];
                    } else {
                        // Half slot: two domain values per room-timeslot (first and second half)
                        $this->domains[$varIndex][] = [
                            'room_id' => $room->id,
                            'time_slot_id' => $timeSlot->id,
                            'slot' => self::FIRST_HALF,
                        ];

                        $this->domains[$varIndex][] = [
                            'room_id' => $room->id,
                            'time_slot_id' => $timeSlot->id,
                            'slot' => self::SECOND_HALF,
                        ];
                    }
                }
            }

            // Update statistics
            $type = $variable['type'];
            if (isset($domainStats[$type])) {
                $domainStats[$type]['count']++;
                $domainStats[$type]['total_size'] += count($this->domains[$varIndex]);
            }

            if (empty($this->domains[$varIndex])) {
                Log::error("Variable {$varIndex} has EMPTY domain after generation! Type: {$variable['type']}");
                $emptyDomains++;
            }
        }

        // Log domain statistics
        Log::info("Domain creation complete:");
        foreach ($domainStats as $type => $stats) {
            if ($stats['count'] > 0) {
                $avgSize = round($stats['total_size'] / $stats['count'], 2);
                Log::info("  - {$type}: {$stats['count']} variables, avg domain size: {$avgSize}");
            }
        }

        if ($emptyDomains > 0) {
            Log::error("WARNING: {$emptyDomains} variables have empty domains!");
        }
    }

    /**
     * Get suitable rooms for a component type
     */
    private function getRoomsForType(string $type)
    {
        switch ($type) {
            case 'Lecture':
                return $this->rooms->filter(function ($room) {
                    return in_array($room->type, ['Classroom', 'Theater', 'Hall'])
                        && $room->capacity > 25;
                });

            case 'Lab':
                return $this->rooms->filter(function ($room) {
                    return str_contains($room->type, 'Lab')
                        || str_contains($room->type, 'Studio')
                        || in_array($room->type, [
                            'ComputerLab',
                            'BioLab',
                            'DrawingStudio',
                            'PhysicsLab',
                            'DrawingLab',
                            'Classroom'
                        ]);
                });

            case 'Tutorial':
                return $this->rooms->filter(function ($room) {
                    return $room->type === 'Classroom' && $room->capacity < 50;
                });

            default:
                Log::warning("Unknown component type: {$type}");
                return collect([]);
        }
    }

    /**
     * Build constraint graph (neighbor relationships)
     */
    private function makeNeighbors(): void
    {
        Log::info("Building constraint graph with slot awareness...");

        $count = count($this->variables);

        // Initialize empty neighbor lists
        for ($i = 0; $i < $count; $i++) {
            $this->neighbors[$i] = [];
        }

        $edgeCount = 0;
        $instructorEdges = 0;
        $roomEdges = 0;

        // Build neighbor relationships
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $varI = $this->variables[$i];
                $varJ = $this->variables[$j];

                $needsEdge = false;

                // Variables are neighbors if they share an instructor
                $shareInstructor = !is_null($varI['instructor_id'])
                    && !is_null($varJ['instructor_id'])
                    && $varI['instructor_id'] === $varJ['instructor_id'];

                if ($shareInstructor) {
                    $needsEdge = true;
                    $instructorEdges++;
                }

                // All variables can potentially conflict on room-time-slot combinations
                // This is especially important now with half-slots
                if (!$needsEdge) {
                    $needsEdge = true;
                    $roomEdges++;
                }

                if ($needsEdge) {
                    $this->neighbors[$i][] = $j;
                    $this->neighbors[$j][] = $i;
                    $edgeCount++;
                }
            }
        }

        Log::info("Constraint graph built:");
        Log::info("  - Total edges: {$edgeCount}");
        Log::info("  - Instructor-based edges: {$instructorEdges}");
        Log::info("  - Room-based edges: {$roomEdges}");
        Log::info("  - Average degree: " . round(2 * $edgeCount / max(1, $count), 2));
    }

    public function register(): void {}
    public function boot(): void {}
}
