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
    private $assignment = [];
    private $constraints = [];
    private $courseLevels = [];

    public function generateTimetable()
    {
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

        // Initialize assignment
        $this->assignment = [];

        // Get all variables (course-type combinations)
        $variables = [];
        foreach ($this->domain as $courseId => $types) {
            foreach ($types as $type => $values) {
                $variables[] = ['course_id' => $courseId, 'type' => $type];
            }
        }

        // Run backtracking
        $result = $this->backtrack($variables, 0);

        if ($result) {
            // Save to database
            $this->saveToDatabase();
            $res = $this->getAssignment();

            return response()->json(['success' => true, 'message' => 'Timetable generated successfully', 'data' => $res]);
        } else {
            return response()->json(['success' => false, 'message' => 'No solution found']);
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
                    // Use rooms with capacity > 25 OR capacity is null
                    $rooms = $allRooms->filter(
                        fn($r) =>
                        $r->room_type === 'Classroom' &&
                            ($r->room_capacity > 25 || is_null($r->room_capacity))
                    );
                    $instructors = $qualified->filter(fn($i) => str_starts_with($i->instructor_id, 'PROF'));
                } elseif ($type === 'Tutorial') {
                    // Use rooms with capacity <= 25
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
                            // If no qualified instructors, still add slot-room combinations
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

    private function backtrack($variables, $index)
    {
        // Base case: all variables assigned
        if ($index >= count($variables)) {
            return true;
        }

        $variable = $variables[$index];
        $courseId = $variable['course_id'];
        $type = $variable['type'];

        // Get domain values for this variable
        $domainValues = $this->domain[$courseId][$type];

        // Try each value in the domain
        foreach ($domainValues as $value) {
            // Check if assignment is consistent with constraints
            if ($this->isConsistent($courseId, $type, $value)) {
                // Make assignment
                $this->assignment[$courseId][$type] = $value;

                // Recursive call
                if ($this->backtrack($variables, $index + 1)) {
                    return true;
                }

                // Backtrack: remove assignment
                unset($this->assignment[$courseId][$type]);
            }
        }

        return false;
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
            // Clear existing timetable entries (optional)
            Timetable::whereNotNull('course_id')->delete();

            foreach ($this->assignment as $courseId => $types) {
                // Get the level for this course
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

    // Method to get current assignment (for debugging)
    public function getAssignment()
    {
        return $this->assignment;
    }
}
