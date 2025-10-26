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

    // Track instructor assignments for balancing
    private array $instructorWorkload = [];

    // Cache for frequently accessed data
    private $timeSlots;
    private $rooms;

    // Organized rooms by type
    private $roomsByType = [];

    // Track which room subset each variable gets
    private int $roomRotationIndex = 0;

    // Slot configurations
    private const FULL_SLOT = 'full';
    private const FIRST_HALF = 'first_half';
    private const SECOND_HALF = 'second_half';

    // OPTIMIZATION: Domain size control
    private const ROOMS_PER_VARIABLE = 25;      // Each variable gets 25 rooms
    private const ROOM_OVERLAP_PERCENTAGE = 40;  // 40% overlap between consecutive variables
    private const MIN_ROOMS_REQUIRED = 15;       // Minimum acceptable room count

    public function __construct()
    {
        try {
            Log::info("=== Initializing Variable Manager (Rotating Room Distribution) ===");
            Log::info("Memory at start: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB");

            $startTime = microtime(true);

            Log::info("Loading time slots...");
            $this->timeSlots = TimeSlot::all();
            Log::info("Loaded {$this->timeSlots->count()} time slots");

            Log::info("Loading rooms...");
            $this->rooms = Room::all();
            Log::info("Loaded {$this->rooms->count()} total rooms");

            $this->categorizeRooms();

            Log::info("Making variables with instructor balancing...");
            $this->makeVariables();
            Log::info("Created " . count($this->variables) . " variables");
            Log::info("Memory after variables: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB");

            $this->logInstructorDistribution();

            Log::info("Making domains with rotating room distribution...");
            $this->makeDomains();
            Log::info("Created " . count($this->domains) . " domains");
            Log::info("Memory after domains: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB");

            Log::info("Making neighbors...");
            $this->makeNeighbors();
            Log::info("Memory after neighbors: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB");

            $totalTime = microtime(true) - $startTime;

            Log::info("=== Variable Manager Summary ===");
            Log::info("Total variables: " . count($this->variables));
            Log::info("Total domains: " . count($this->domains));

            $totalDomainValues = array_sum(array_map('count', $this->domains));
            Log::info("Total domain values: " . number_format($totalDomainValues));
            Log::info("Average domain size: " . round($totalDomainValues / max(1, count($this->domains)), 2));
            Log::info("Initialization time: " . round($totalTime, 3) . "s");
            Log::info("Peak memory: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB");

            $this->logRoomDistribution();

            Log::info("===================================");
        } catch (\Exception $e) {
            Log::error("FATAL ERROR in VariableManager constructor!");
            Log::error("Error: " . $e->getMessage());
            Log::error("File: " . $e->getFile() . " line " . $e->getLine());
            throw $e;
        }
    }

    /**
     * Categorize and shuffle rooms for better distribution
     */
    private function categorizeRooms(): void
    {
        Log::info("Categorizing and shuffling rooms...");

        // Get lecture rooms and shuffle them for variety
        $lectureRooms = $this->rooms->filter(function ($room) {
            return in_array($room->type, ['Classroom', 'Theater', 'Hall'])
                && $room->capacity > 25;
        })->shuffle()->values();  // Shuffle to randomize distribution

        $this->roomsByType['Lecture'] = $lectureRooms;
        Log::info("  - Lecture rooms: {$lectureRooms->count()}");

        // Lab rooms - shuffle for variety
        $labRooms = $this->rooms->filter(function ($room) {
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
        })->shuffle()->values();

        $this->roomsByType['Lab'] = $labRooms;
        Log::info("  - Lab rooms: {$labRooms->count()}");

        // Tutorial rooms - shuffle
        $tutorialRooms = $this->rooms->filter(function ($room) {
            return $room->type === 'Classroom' && $room->capacity < 50;
        })->shuffle()->values();

        $this->roomsByType['Tutorial'] = $tutorialRooms;
        Log::info("  - Tutorial rooms: {$tutorialRooms->count()}");
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
     * Create variables with balanced instructor assignment
     */
    private function makeVariables(): void
    {
        Log::info("Creating variables with instructor balancing...");

        $requiredCourses = RequiredCourse::with(['course.instructors.roles', 'course.components'])->get();

        if ($requiredCourses->isEmpty()) {
            Log::warning("No required courses found!");
            return;
        }

        Log::info("Processing {$requiredCourses->count()} required courses");

        $variableCount = 0;
        $skippedCourses = 0;

        foreach ($requiredCourses as $requiredCourse) {
            try {
                $course = $requiredCourse->course;

                if (!$course) {
                    $skippedCourses++;
                    continue;
                }

                $capacity = $requiredCourse->required_capacity;

                if ($capacity <= 0) {
                    $skippedCourses++;
                    continue;
                }

                $groupCount = (int) ceil($capacity / 90);
                $sectionCount = (int) ceil($capacity / 30);

                if (!$course->components || $course->components->isEmpty()) {
                    $skippedCourses++;
                    continue;
                }

                foreach ($course->components as $component) {
                    if (!isset($component->type)) {
                        continue;
                    }

                    $instructor = $this->selectInstructorBalanced($course, $component->type, $requiredCourse);

                    $count = ($component->type === 'Lecture') ? $groupCount : $sectionCount;
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
                            'slot_type' => $slotType,
                        ];
                        $variableCount++;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error processing course: " . $e->getMessage());
                $skippedCourses++;
            }
        }

        Log::info("Created {$variableCount} variables ({$skippedCourses} skipped)");
    }

    /**
     * Select instructor with load balancing
     */
    private function selectInstructorBalanced($course, string $componentType, $requiredCourse)
    {
        if ($componentType === 'Lecture') {
            // If there's a preferred instructor, use them
            if (!is_null($requiredCourse) && !is_null($requiredCourse->instructor_id)) {
                $instructor = $course->instructors()
                    ->where('id', $requiredCourse->instructor_id)
                    ->first();

                if ($instructor) {
                    $this->instructorWorkload[$instructor->id] =
                        ($this->instructorWorkload[$instructor->id] ?? 0) + 1;
                    return $instructor;
                }
            }

            // No preference: Select least-loaded professor
            $professors = $course->instructors()
                ->whereHas('roles', fn($q) => $q->where('role', 'prof'))
                ->get();

            if ($professors->isEmpty()) {
                return null;
            }

            // Find professor with minimum workload
            $selectedInstructor = null;
            $minWorkload = PHP_INT_MAX;

            foreach ($professors as $prof) {
                $currentLoad = $this->instructorWorkload[$prof->id] ?? 0;

                if ($currentLoad < $minWorkload) {
                    $minWorkload = $currentLoad;
                    $selectedInstructor = $prof;
                }
            }

            if ($selectedInstructor) {
                $this->instructorWorkload[$selectedInstructor->id] =
                    ($this->instructorWorkload[$selectedInstructor->id] ?? 0) + 1;
            }

            return $selectedInstructor;
        }

        return null;
    }

    /**
     * Log instructor distribution
     */
    private function logInstructorDistribution(): void
    {
        if (empty($this->instructorWorkload)) {
            Log::info("No instructors assigned");
            return;
        }

        Log::info("=== Instructor Workload Distribution ===");
        arsort($this->instructorWorkload);

        $totalAssignments = array_sum($this->instructorWorkload);
        $avgLoad = $totalAssignments / count($this->instructorWorkload);

        Log::info("Total instructor assignments: {$totalAssignments}");
        Log::info("Number of instructors: " . count($this->instructorWorkload));
        Log::info("Average load per instructor: " . round($avgLoad, 2));

        Log::info("Top instructors by load:");
        $count = 0;
        foreach ($this->instructorWorkload as $instructorId => $load) {
            if ($count++ >= 5) break;
            Log::info("  - Instructor {$instructorId}: {$load} courses");
        }

        $maxLoad = max($this->instructorWorkload);
        $minLoad = min($this->instructorWorkload);

        if ($maxLoad - $minLoad > 5) {
            Log::warning("Workload imbalance detected! Max: {$maxLoad}, Min: {$minLoad}, Difference: " . ($maxLoad - $minLoad));
        } else {
            Log::info("Workload well balanced (max-min difference: " . ($maxLoad - $minLoad) . ")");
        }
    }

    private function getSlotTypeForComponent(string $componentType): string
    {
        switch ($componentType) {
            case 'Lecture':
            case 'Lab':
                return self::FULL_SLOT;
            case 'Tutorial':
                return self::FIRST_HALF;
            default:
                return self::FULL_SLOT;
        }
    }

    /**
     * Create domains with ROTATING room distribution strategy
     */
    private function makeDomains(): void
    {
        Log::info("Creating domains with rotating room distribution...");
        Log::info("Strategy: Each variable gets " . self::ROOMS_PER_VARIABLE . " rooms with " . self::ROOM_OVERLAP_PERCENTAGE . "% overlap");

        if (empty($this->variables)) {
            throw new \Exception("No variables to create domains for");
        }

        $emptyDomains = 0;
        $domainStats = [
            'Lecture' => ['count' => 0, 'total_size' => 0, 'unique_rooms' => []],
            'Lab' => ['count' => 0, 'total_size' => 0, 'unique_rooms' => []],
            'Tutorial' => ['count' => 0, 'total_size' => 0, 'unique_rooms' => []],
        ];

        $batchSize = 50;
        $totalVariables = count($this->variables);

        foreach ($this->variables as $varIndex => $variable) {
            if ($varIndex % $batchSize === 0) {
                $progress = round(($varIndex / $totalVariables) * 100, 1);
                $memMB = round(memory_get_usage() / 1024 / 1024, 2);
                Log::info("Progress: {$progress}% ({$varIndex}/{$totalVariables}) - Memory: {$memMB} MB");

                if ($varIndex % 100 === 0 && $varIndex > 0) {
                    gc_collect_cycles();
                }
            }

            $type = $variable['type'];

            // Get rotating subset of rooms for this variable
            $rooms = $this->getRotatingRoomSubset($type, $varIndex);

            if ($rooms->isEmpty()) {
                Log::error("No suitable rooms for variable {$varIndex} (Type: {$type})");
                $this->domains[$varIndex] = [];
                $emptyDomains++;
                continue;
            }

            $this->domains[$varIndex] = [];
            $slotType = $variable['slot_type'];

            // Build domain values
            foreach ($rooms as $room) {
                foreach ($this->timeSlots as $timeSlot) {
                    if ($slotType === self::FULL_SLOT) {
                        $this->domains[$varIndex][] = [
                            'room_id' => $room->id,
                            'time_slot_id' => $timeSlot->id,
                            'slot' => self::FULL_SLOT,
                        ];
                    } else {
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

            $domainSize = count($this->domains[$varIndex]);

            // Track statistics
            if (isset($domainStats[$type])) {
                $domainStats[$type]['count']++;
                $domainStats[$type]['total_size'] += $domainSize;

                // Track unique rooms used
                foreach ($rooms as $room) {
                    $domainStats[$type]['unique_rooms'][$room->id] = true;
                }
            }

            if (empty($this->domains[$varIndex])) {
                Log::error("Empty domain for variable {$varIndex} (Type: {$type})");
                $emptyDomains++;
            }
        }

        Log::info("Domain creation complete:");
        foreach ($domainStats as $type => $stats) {
            if ($stats['count'] > 0) {
                $avgSize = round($stats['total_size'] / $stats['count'], 2);
                $uniqueRooms = count($stats['unique_rooms']);
                Log::info("  - {$type}: {$stats['count']} variables");
                Log::info("    * Average domain size: {$avgSize}");
                Log::info("    * Unique rooms used: {$uniqueRooms}");
                Log::info("    * Room coverage: " . round(($uniqueRooms / max(1, $this->roomsByType[$type]->count())) * 100, 1) . "%");
            }
        }

        if ($emptyDomains > 0) {
            Log::error("WARNING: {$emptyDomains} variables have empty domains!");
        }
    }

    /**
     * Get a rotating subset of rooms for variety
     * Each variable gets different rooms with controlled overlap
     */
    private function getRotatingRoomSubset(string $type, int $varIndex)
    {
        $allRooms = $this->roomsByType[$type] ?? collect([]);

        if ($allRooms->isEmpty()) {
            return collect([]);
        }

        $totalRooms = $allRooms->count();

        // If we have fewer rooms than needed, use all
        if ($totalRooms <= self::ROOMS_PER_VARIABLE) {
            return $allRooms;
        }

        // Calculate step size for rotation (controls overlap)
        // If overlap is 40%, then step = 60% of ROOMS_PER_VARIABLE
        $stepSize = (int) (self::ROOMS_PER_VARIABLE * (1 - self::ROOM_OVERLAP_PERCENTAGE / 100));
        $stepSize = max(1, $stepSize); // At least move by 1

        // Calculate starting index for this variable
        $startIndex = ($varIndex * $stepSize) % $totalRooms;

        // Collect rooms in a circular manner
        $selectedRooms = collect([]);
        for ($i = 0; $i < self::ROOMS_PER_VARIABLE; $i++) {
            $index = ($startIndex + $i) % $totalRooms;
            $selectedRooms->push($allRooms[$index]);
        }

        return $selectedRooms;
    }

    /**
     * Log room distribution statistics
     */
    private function logRoomDistribution(): void
    {
        Log::info("=== Room Distribution Analysis ===");

        // Count how many variables can use each room
        $roomUsageCounts = [];

        foreach ($this->domains as $varIndex => $domain) {
            $roomsInDomain = [];
            foreach ($domain as $value) {
                $roomsInDomain[$value['room_id']] = true;
            }

            foreach (array_keys($roomsInDomain) as $roomId) {
                $roomUsageCounts[$roomId] = ($roomUsageCounts[$roomId] ?? 0) + 1;
            }
        }

        if (empty($roomUsageCounts)) {
            Log::warning("No room usage data available");
            return;
        }

        $usageCounts = array_values($roomUsageCounts);
        $avgUsage = array_sum($usageCounts) / count($usageCounts);
        $maxUsage = max($usageCounts);
        $minUsage = min($usageCounts);

        Log::info("Rooms in use: " . count($roomUsageCounts) . " out of " . $this->rooms->count());
        Log::info("Average variables per room: " . round($avgUsage, 2));
        Log::info("Most used room: {$maxUsage} variables can use it");
        Log::info("Least used room: {$minUsage} variables can use it");
        Log::info("Usage spread (max-min): " . ($maxUsage - $minUsage));

        // Check for good distribution
        $variance = 0;
        foreach ($usageCounts as $count) {
            $variance += pow($count - $avgUsage, 2);
        }
        $stdDev = sqrt($variance / count($usageCounts));

        Log::info("Usage standard deviation: " . round($stdDev, 2));

        if ($stdDev < $avgUsage * 0.3) {
            Log::info("✓ Room distribution is EXCELLENT (low variance)");
        } elseif ($stdDev < $avgUsage * 0.5) {
            Log::info("✓ Room distribution is GOOD");
        } else {
            Log::warning("⚠ Room distribution has high variance - some rooms heavily favored");
        }
    }

    /**
     * Build constraint graph - OPTIMIZED with room overlap analysis
     * Only creates edges between variables that can actually conflict
     */
    private function makeNeighbors(): void
    {
        Log::info("Building optimized constraint graph with room overlap analysis...");

        $startTime = microtime(true);
        $count = count($this->variables);

        if ($count === 0) {
            return;
        }

        // Initialize
        for ($i = 0; $i < $count; $i++) {
            $this->neighbors[$i] = [];
        }

        // OPTIMIZATION 1: Pre-compute room sets for each variable
        Log::info("Step 1: Computing room sets for each variable...");
        $variableRoomSets = [];
        foreach ($this->domains as $varIndex => $domain) {
            $roomSet = [];
            foreach ($domain as $value) {
                $roomSet[$value['room_id']] = true;
            }
            $variableRoomSets[$varIndex] = $roomSet;
        }

        Log::info("Step 2: Building edges with overlap detection...");

        $edgeCount = 0;
        $instructorEdges = 0;
        $roomOverlapEdges = 0;
        $skippedPairs = 0;

        // Track edge reasons for analysis
        $edgeReasons = [
            'instructor_only' => 0,
            'room_overlap_only' => 0,
            'both' => 0,
        ];

        for ($i = 0; $i < $count; $i++) {
            if ($i % 50 === 0 && $i > 0) {
                $progress = round(($i / $count) * 100, 1);
                Log::info("Building edges: {$progress}% ({$i}/{$count})...");
            }

            for ($j = $i + 1; $j < $count; $j++) {
                $varI = $this->variables[$i];
                $varJ = $this->variables[$j];

                // Check instructor constraint
                $shareInstructor = !is_null($varI['instructor_id'])
                    && !is_null($varJ['instructor_id'])
                    && $varI['instructor_id'] === $varJ['instructor_id'];

                // OPTIMIZATION 2: Check if variables share ANY rooms
                $shareRooms = $this->doVariablesShareRooms(
                    $variableRoomSets[$i],
                    $variableRoomSets[$j]
                );

                // Only create edge if there's a potential conflict
                $needsEdge = $shareInstructor || $shareRooms;

                if ($needsEdge) {
                    $this->neighbors[$i][] = $j;
                    $this->neighbors[$j][] = $i;
                    $edgeCount++;

                    // Track edge type for analysis
                    if ($shareInstructor && $shareRooms) {
                        $edgeReasons['both']++;
                    } elseif ($shareInstructor) {
                        $edgeReasons['instructor_only']++;
                        $instructorEdges++;
                    } else {
                        $edgeReasons['room_overlap_only']++;
                        $roomOverlapEdges++;
                    }
                } else {
                    $skippedPairs++;
                }
            }
        }

        $buildTime = microtime(true) - $startTime;

        Log::info("=== Constraint Graph Statistics ===");
        Log::info("Total edges: {$edgeCount}");
        Log::info("  - Instructor conflicts: {$instructorEdges}");
        Log::info("  - Room overlap conflicts: {$roomOverlapEdges}");
        Log::info("  - Both constraints: {$edgeReasons['both']}");
        Log::info("Pairs skipped (no conflict): {$skippedPairs}");

        $totalPairs = ($count * ($count - 1)) / 2;
        $densityPercent = round(($edgeCount / max(1, $totalPairs)) * 100, 2);
        Log::info("Graph density: {$densityPercent}% ({$edgeCount}/{$totalPairs} possible edges)");

        $avgDegree = round(2 * $edgeCount / max(1, $count), 2);
        Log::info("Average degree: {$avgDegree}");

        Log::info("Build time: " . round($buildTime, 2) . "s");

        // Performance analysis
        if ($densityPercent > 80) {
            Log::warning("⚠ Graph is very dense ({$densityPercent}%)! This will slow down solving.");
            Log::warning("Consider: reducing room overlap or increasing ROOMS_PER_VARIABLE");
        } elseif ($densityPercent > 60) {
            Log::info("Graph density is moderate ({$densityPercent}%) - should solve reasonably fast");
        } else {
            Log::info("✓ Graph is sparse ({$densityPercent}%) - optimal for fast solving!");
        }

        Log::info("===================================");
    }

    /**
     * Check if two variables share any rooms (can potentially conflict)
     * Uses set intersection for O(min(n,m)) complexity
     */
    private function doVariablesShareRooms(array $roomSetI, array $roomSetJ): bool
    {
        // Optimization: iterate over smaller set
        if (count($roomSetI) > count($roomSetJ)) {
            [$roomSetI, $roomSetJ] = [$roomSetJ, $roomSetI];
        }

        // Check if any room exists in both sets
        foreach ($roomSetI as $roomId => $unused) {
            if (isset($roomSetJ[$roomId])) {
                return true;  // Found shared room
            }
        }

        return false;  // No shared rooms
    }

    public function register(): void {}
    public function boot(): void {}
}
