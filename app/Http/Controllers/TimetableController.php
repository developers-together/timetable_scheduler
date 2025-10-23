<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\Room;
use App\Models\Timetable;
use Illuminate\Support\Facades\DB;

class TimetableController extends Controller
{
    private $domain = [];
    private $originalDomain = [];
    private $assignment = [];
    private $courseLevels = [];
    private $statistics = [
        'backtracks' => 0,
        'consistency_checks' => 0,
        'forward_checks' => 0,
    ];

    public function generateTimetable()
    {
        $startTime = microtime(true);

        // Define course levels mapping
        $this->courseLevels = [
            'CSC111' => 1,
            'ECE111' => 1,
            'LRA101' => 1,
            'LRA103' => 1,
            'LRA104' => 1,
            'LRA105' => 1,
            'LRA401' => 1,
            'MTH111' => 1,
            'PHY113' => 1,
            'CNC111' => 2,
            'CSC211' => 2,
            'CSE214' => 2,
            'LRA306' => 2,
            'LRA403' => 2,
            'MTH212' => 2,
            'ACM215' => 2,
            'CSC114' => 2
        ];

        $courseids = array_keys($this->courseLevels);

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

        // Build domain
        $this->domain = $this->buildDomain($courseids, $slots);
        $this->originalDomain = $this->domain; // Keep a copy

        // Initialize assignment
        $this->assignment = [];

        // Get all variables (course-type combinations)
        $variables = [];
        foreach ($this->domain as $courseId => $types) {
            foreach ($types as $type => $values) {
                $variables[] = ['course_id' => $courseId, 'type' => $type];
            }
        }

        // Run backtracking with optimizations
        $result = $this->backtrackWithHeuristics($variables);

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);

        if ($result) {
            // Save to database
            $this->saveToDatabase();
            $res = $this->getAssignment();

            return response()->json([
                'success' => true,
                'message' => 'Timetable generated successfully',
                'execution_time' => $executionTime . 's',
                'statistics' => $this->statistics,
                'assignments' => count($this->assignment),
                'data' => $res
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No solution found',
                'execution_time' => $executionTime . 's',
                'statistics' => $this->statistics
            ]);
        }
    }

    private function buildDomain($courseids, $slots)
    {
        $courses = Course::with('qualifiedInstructors')->whereIn('course_id', $courseids)->get();
        $allRooms = Room::all();

        $domain = [];

        foreach ($courses as $course) {
            $types = explode(',', $course->course_type);
            $qualified = $course->qualifiedInstructors;

            foreach ($types as $type) {
                $domain[$course->course_id][$type] = [];

                // Determine rooms and instructors based on type
                if ($type === 'Lecture') {
                    $rooms = $allRooms->filter(
                        fn($r) =>
                        $r->room_type === 'Classroom' &&
                            ($r->room_capacity > 25 || is_null($r->room_capacity))
                    );
                    $instructors = $qualified->filter(fn($i) => str_starts_with($i->instructor_id, 'PROF'));
                } elseif ($type === 'Tutorial') {
                    $rooms = $allRooms->filter(
                        fn($r) =>
                        $r->room_type === 'Classroom' &&
                            $r->room_capacity <= 25 &&
                            !is_null($r->room_capacity)
                    );
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

        return $domain;
    }

    /**
     * Optimized backtracking with MRV, Degree Heuristic, LCV, and Forward Checking
     */
    private function backtrackWithHeuristics($variables)
    {
        // Base case: all variables assigned
        if (count($this->assignment) >= count($variables)) {
            return true;
        }

        // MRV + Degree Heuristic: Select unassigned variable with smallest domain
        $variable = $this->selectUnassignedVariable($variables);

        if (!$variable) {
            return true; // All assigned
        }

        $courseId = $variable['course_id'];
        $type = $variable['type'];

        // LCV: Order domain values by least constraining
        $domainValues = $this->orderDomainValues($courseId, $type, $variables);

        // Try each value in the ordered domain
        foreach ($domainValues as $value) {
            $this->statistics['consistency_checks']++;

            // Check if assignment is consistent with constraints
            if ($this->isConsistent($courseId, $type, $value)) {
                // Make assignment
                $this->assignment[$courseId][$type] = $value;

                // Forward Checking: Remove inconsistent values from future domains
                $removedValues = $this->forwardCheck($courseId, $type, $value, $variables);

                // Recursive call
                if ($this->backtrackWithHeuristics($variables)) {
                    return true;
                }

                // Backtrack: remove assignment and restore domains
                $this->statistics['backtracks']++;
                unset($this->assignment[$courseId][$type]);
                $this->restoreDomains($removedValues);
            }
        }

        return false;
    }

    /**
     * MRV Heuristic: Select variable with Minimum Remaining Values
     * Ties broken by Degree Heuristic (most constraints)
     */
    private function selectUnassignedVariable($variables)
    {
        $unassigned = array_filter($variables, function ($var) {
            return !isset($this->assignment[$var['course_id']][$var['type']]);
        });

        if (empty($unassigned)) {
            return null;
        }

        $minDomainSize = PHP_INT_MAX;
        $selected = null;
        $maxDegree = -1;

        foreach ($unassigned as $var) {
            $courseId = $var['course_id'];
            $type = $var['type'];
            $domainSize = count($this->domain[$courseId][$type]);

            // MRV: Choose variable with smallest domain
            if ($domainSize < $minDomainSize) {
                $minDomainSize = $domainSize;
                $selected = $var;
                $maxDegree = $this->calculateDegree($var, $unassigned);
            } elseif ($domainSize === $minDomainSize) {
                // Degree Heuristic: Break ties with most constraints
                $degree = $this->calculateDegree($var, $unassigned);
                if ($degree > $maxDegree) {
                    $selected = $var;
                    $maxDegree = $degree;
                }
            }
        }

        return $selected;
    }

    /**
     * Calculate degree (number of constraints with other unassigned variables)
     */
    private function calculateDegree($variable, $unassigned)
    {
        $degree = 0;

        // Count how many unassigned variables this variable constrains
        foreach ($unassigned as $other) {
            if ($variable !== $other) {
                // Variables constrain each other if they share instructors or rooms
                $degree++;
            }
        }

        return $degree;
    }

    /**
     * LCV Heuristic: Order values by Least Constraining Value
     * (values that rule out fewest options for neighboring variables)
     */
    private function orderDomainValues($courseId, $type, $variables)
    {
        $domainValues = $this->domain[$courseId][$type];

        // Calculate how constraining each value is
        $valueConstraints = [];

        foreach ($domainValues as $index => $value) {
            $constraintCount = 0;

            // Count how many values this would eliminate from other variables
            foreach ($variables as $var) {
                if ($var['course_id'] === $courseId && $var['type'] === $type) {
                    continue; // Skip self
                }

                if (isset($this->assignment[$var['course_id']][$var['type']])) {
                    continue; // Skip assigned
                }

                $otherDomain = $this->domain[$var['course_id']][$var['type']];

                foreach ($otherDomain as $otherValue) {
                    if (!$this->isConsistentPair($value, $otherValue)) {
                        $constraintCount++;
                    }
                }
            }

            $valueConstraints[$index] = $constraintCount;
        }

        // Sort by constraint count (ascending - least constraining first)
        asort($valueConstraints);

        $orderedValues = [];
        foreach (array_keys($valueConstraints) as $index) {
            $orderedValues[] = $domainValues[$index];
        }

        return $orderedValues;
    }

    /**
     * Check if two values are consistent with each other
     */
    private function isConsistentPair($value1, $value2)
    {
        if ($value1['slot'] !== $value2['slot']) {
            return true; // Different slots, no conflict
        }

        // Same slot - check instructor and room conflicts
        if (
            $value1['instructor_id'] && $value2['instructor_id'] &&
            $value1['instructor_id'] === $value2['instructor_id']
        ) {
            return false; // Instructor conflict
        }

        if ($value1['room_id'] === $value2['room_id']) {
            return false; // Room conflict
        }

        return true;
    }

    /**
     * Forward Checking: Remove inconsistent values from future variable domains
     */
    private function forwardCheck($courseId, $type, $value, $variables)
    {
        $this->statistics['forward_checks']++;
        $removed = [];

        foreach ($variables as $var) {
            if ($var['course_id'] === $courseId && $var['type'] === $type) {
                continue; // Skip current variable
            }

            if (isset($this->assignment[$var['course_id']][$var['type']])) {
                continue; // Skip assigned variables
            }

            $varCourseId = $var['course_id'];
            $varType = $var['type'];
            $toRemove = [];

            foreach ($this->domain[$varCourseId][$varType] as $index => $domainValue) {
                if (!$this->isConsistentPair($value, $domainValue)) {
                    $toRemove[] = $index;
                }
            }

            if (!empty($toRemove)) {
                $removed[$varCourseId][$varType] = [];

                // Remove inconsistent values (in reverse to maintain indices)
                foreach (array_reverse($toRemove) as $index) {
                    $removed[$varCourseId][$varType][$index] =
                        $this->domain[$varCourseId][$varType][$index];
                    unset($this->domain[$varCourseId][$varType][$index]);
                }

                // Re-index array
                $this->domain[$varCourseId][$varType] =
                    array_values($this->domain[$varCourseId][$varType]);
            }
        }

        return $removed;
    }

    /**
     * Restore domains after backtracking
     */
    private function restoreDomains($removedValues)
    {
        foreach ($removedValues as $courseId => $types) {
            foreach ($types as $type => $values) {
                foreach ($values as $index => $value) {
                    $this->domain[$courseId][$type][] = $value;
                }
            }
        }
    }

    private function isConsistent($courseId, $type, $value)
    {
        $slot = $value['slot'];
        $roomId = $value['room_id'];
        $instructorId = $value['instructor_id'];

        // Check all existing assignments
        foreach ($this->assignment as $assignedCourseId => $types) {
            foreach ($types as $assignedType => $assignedValue) {
                $assignedSlot = $assignedValue['slot'];
                $assignedRoomId = $assignedValue['room_id'];
                $assignedInstructorId = $assignedValue['instructor_id'];

                // Only check if same time slot
                if ($slot === $assignedSlot) {
                    // Hard Constraint 1: No instructor can teach multiple classes at same time
                    if (
                        $instructorId && $assignedInstructorId &&
                        $instructorId === $assignedInstructorId
                    ) {
                        return false;
                    }

                    // Hard Constraint 2: No room can host multiple classes at same time
                    if ($roomId === $assignedRoomId) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    private function saveToDatabase()
    {
        DB::beginTransaction();

        try {
            // Clear existing timetable entries
            Timetable::whereNotNull('course_id')->delete();

            foreach ($this->assignment as $courseId => $types) {
                $level = $this->courseLevels[$courseId] ?? 1;

                foreach ($types as $type => $value) {
                    $slotParts = explode('-', $value['slot']);
                    $day = $slotParts[0];
                    $startTime = $slotParts[1];
                    $endTime = $slotParts[2];

                    Timetable::create([
                        'day' => $day,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'level' => (string)$level,
                        'room_id' => $value['room_id'],
                        'course_id' => $courseId,
                        'instructor_id' => $value['instructor_id'],
                    ]);
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getAssignment()
    {
        return $this->assignment;
    }

    public function getStatistics()
    {
        return $this->statistics;
    }
}
