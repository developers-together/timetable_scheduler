<?php

namespace App\Providers\CSP;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Schedule;
use App\Models\RequiredCourse;

class DatabaseSaverProvider extends ServiceProvider
{

    public function saveOnDb(array $assignment, int $score, array $variables): void
    {
        Log::info("Saving schedule to database with slot information...");

        // CRITICAL: Validate assignment before saving
        $conflicts = $this->validateAssignment($assignment, $variables);

        if (!empty($conflicts)) {
            Log::error("Assignment validation failed! Found conflicts:");
            foreach (array_slice($conflicts, 0, 10) as $conflict) {
                Log::error("  - {$conflict}");
            }
            throw new \Exception("Assignment has " . count($conflicts) . " conflicts! Cannot save.");
        }

        DB::beginTransaction();

        try {
            $savedCount = 0;
            $errors = [];
            $slotStats = [
                'full' => 0,
                'first_half' => 0,
                'second_half' => 0,
            ];

            foreach ($assignment as $varIndex => $assignedValue) {
                $variable = $variables[$varIndex];

                $reqCourse = RequiredCourse::where('course_id', $variable['course_id'])->first();

                if (!$reqCourse) {
                    $errors[] = "Required course not found for course_id: {$variable['course_id']}";
                    Log::warning("Required course not found for variable {$varIndex}, course_id: {$variable['course_id']}");
                    continue;
                }

                $slot = $assignedValue['slot'] ?? 'full';

                if (!in_array($slot, ['full', 'first_half', 'second_half'])) {
                    Log::warning("Invalid slot value '{$slot}' for variable {$varIndex}, defaulting to 'full'");
                    $slot = 'full';
                }

                try {
                    Schedule::create([
                        'level' => $reqCourse->level,
                        'term' => $reqCourse->term,
                        'faculty' => $reqCourse->faculty,
                        'slot' => $slot,
                        'course_id' => $variable['course_id'],
                        'course_component_id' => $variable['type'],
                        'instructor_id' => $variable['instructor_id'],
                        'room_id' => $assignedValue['room_id'],
                        'time_slot_id' => $assignedValue['time_slot_id'],
                        'groupNO' => $variable['groupNO'],
                        'sectionNO' => $variable['sectionNO'],
                    ]);

                    $savedCount++;
                    $slotStats[$slot] = ($slotStats[$slot] ?? 0) + 1;
                } catch (\Exception $e) {
                    $errors[] = "Failed to save variable {$varIndex}: " . $e->getMessage();
                    Log::error("Failed to save variable {$varIndex}: " . $e->getMessage());
                }
            }

            DB::commit();

            Log::info("Successfully saved {$savedCount} schedule entries");
            Log::info("Slot distribution:");
            Log::info("  - Full slots: {$slotStats['full']}");
            Log::info("  - First half slots: {$slotStats['first_half']}");
            Log::info("  - Second half slots: {$slotStats['second_half']}");

            if (!empty($errors)) {
                Log::warning("Encountered " . count($errors) . " errors during save:");
                foreach (array_slice($errors, 0, 5) as $error) {
                    Log::warning("  - {$error}");
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Database transaction failed: " . $e->getMessage());
            throw new \Exception("Failed to save schedule to database: " . $e->getMessage());
        }
    }

    /**
     * Validate assignment for conflicts BEFORE saving to database
     */
    private function validateAssignment(array $assignment, array $variables): array
    {
        $conflicts = [];

        // Track room usage: [room_id][time_slot_id][slot] = variable_index
        $roomUsage = [];

        // Track instructor usage: [instructor_id][time_slot_id][slot] = variable_index
        $instructorUsage = [];

        foreach ($assignment as $varIndex => $value) {
            $variable = $variables[$varIndex];

            $roomId = $value['room_id'];
            $timeSlotId = $value['time_slot_id'];
            $slot = $value['slot'];
            $instructorId = $variable['instructor_id'] ?? null;

            // Check room conflicts
            if (!isset($roomUsage[$roomId])) {
                $roomUsage[$roomId] = [];
            }

            if (!isset($roomUsage[$roomId][$timeSlotId])) {
                $roomUsage[$roomId][$timeSlotId] = [];
            }

            // Check if this room-time-slot combination conflicts
            if ($slot === 'full') {
                // Full slot conflicts with everything in this time slot
                if (!empty($roomUsage[$roomId][$timeSlotId])) {
                    $conflicts[] = "Variable {$varIndex} (full slot) conflicts with existing assignments in Room {$roomId}, Time {$timeSlotId}";
                }
                $roomUsage[$roomId][$timeSlotId]['full'] = $varIndex;
            } else {
                // Half slot conflicts with full or same half
                if (isset($roomUsage[$roomId][$timeSlotId]['full'])) {
                    $other = $roomUsage[$roomId][$timeSlotId]['full'];
                    $conflicts[] = "Variable {$varIndex} ({$slot}) conflicts with Variable {$other} (full) in Room {$roomId}, Time {$timeSlotId}";
                }

                if (isset($roomUsage[$roomId][$timeSlotId][$slot])) {
                    $other = $roomUsage[$roomId][$timeSlotId][$slot];
                    $conflicts[] = "Variable {$varIndex} ({$slot}) conflicts with Variable {$other} ({$slot}) in Room {$roomId}, Time {$timeSlotId}";
                }

                $roomUsage[$roomId][$timeSlotId][$slot] = $varIndex;
            }

            // Check instructor conflicts
            if ($instructorId !== null) {
                if (!isset($instructorUsage[$instructorId])) {
                    $instructorUsage[$instructorId] = [];
                }

                if (!isset($instructorUsage[$instructorId][$timeSlotId])) {
                    $instructorUsage[$instructorId][$timeSlotId] = [];
                }

                if ($slot === 'full') {
                    if (!empty($instructorUsage[$instructorId][$timeSlotId])) {
                        $conflicts[] = "Variable {$varIndex} (full slot) causes instructor {$instructorId} conflict at Time {$timeSlotId}";
                    }
                    $instructorUsage[$instructorId][$timeSlotId]['full'] = $varIndex;
                } else {
                    if (isset($instructorUsage[$instructorId][$timeSlotId]['full'])) {
                        $other = $instructorUsage[$instructorId][$timeSlotId]['full'];
                        $conflicts[] = "Variable {$varIndex} ({$slot}) causes instructor {$instructorId} conflict with Variable {$other} (full)";
                    }

                    if (isset($instructorUsage[$instructorId][$timeSlotId][$slot])) {
                        $other = $instructorUsage[$instructorId][$timeSlotId][$slot];
                        $conflicts[] = "Variable {$varIndex} ({$slot}) causes instructor {$instructorId} conflict with Variable {$other} ({$slot})";
                    }

                    $instructorUsage[$instructorId][$timeSlotId][$slot] = $varIndex;
                }
            }
        }

        return $conflicts;
    }

    public function resetDB(): void
    {
        try {
            $deletedCount = DB::table('schedules')->count();
            DB::table('schedules')->truncate();
            Log::info("Database reset: removed {$deletedCount} existing schedule entries");
        } catch (\Exception $e) {
            Log::error("Failed to reset database: " . $e->getMessage());
            throw new \Exception("Failed to reset database: " . $e->getMessage());
        }
    }

    public function validateDatabase(): array
    {
        $validation = [
            'valid' => true,
            'errors' => [],
        ];

        try {
            DB::table('schedules')->limit(1)->get();
        } catch (\Exception $e) {
            $validation['valid'] = false;
            $validation['errors'][] = "Schedules table does not exist or is inaccessible";
        }

        $reqCourseCount = RequiredCourse::count();
        if ($reqCourseCount === 0) {
            $validation['valid'] = false;
            $validation['errors'][] = "No required courses found in database";
        }

        return $validation;
    }

    public function getSlotUsageStats(): array
    {
        try {
            $stats = DB::table('schedules')
                ->select('slot', DB::raw('count(*) as count'))
                ->groupBy('slot')
                ->get()
                ->keyBy('slot')
                ->map(fn($item) => $item->count)
                ->toArray();

            return $stats;
        } catch (\Exception $e) {
            Log::error("Failed to get slot usage stats: " . $e->getMessage());
            return [];
        }
    }

    public function validateSavedSchedule(): array
    {
        $conflicts = [];

        try {
            $roomConflicts = DB::table('schedules as s1')
                ->join('schedules as s2', function ($join) {
                    $join->on('s1.room_id', '=', 's2.room_id')
                        ->on('s1.time_slot_id', '=', 's2.time_slot_id')
                        ->on('s1.id', '<', 's2.id');
                })
                ->where(function ($query) {
                    $query->where('s1.slot', '=', 'full')
                        ->orWhere('s2.slot', '=', 'full')
                        ->orWhereRaw('s1.slot = s2.slot');
                })
                ->select(
                    's1.id as id1',
                    's2.id as id2',
                    's1.room_id',
                    's1.time_slot_id',
                    's1.slot as slot1',
                    's2.slot as slot2'
                )
                ->get();

            if ($roomConflicts->isNotEmpty()) {
                $conflicts['room'] = $roomConflicts->toArray();
                Log::error("Found " . count($conflicts['room']) . " room conflicts in saved schedule!");
            }

            $instructorConflicts = DB::table('schedules as s1')
                ->join('schedules as s2', function ($join) {
                    $join->on('s1.instructor_id', '=', 's2.instructor_id')
                        ->on('s1.time_slot_id', '=', 's2.time_slot_id')
                        ->on('s1.id', '<', 's2.id');
                })
                ->whereNotNull('s1.instructor_id')
                ->where(function ($query) {
                    $query->where('s1.slot', '=', 'full')
                        ->orWhere('s2.slot', '=', 'full')
                        ->orWhereRaw('s1.slot = s2.slot');
                })
                ->select(
                    's1.id as id1',
                    's2.id as id2',
                    's1.instructor_id',
                    's1.time_slot_id',
                    's1.slot as slot1',
                    's2.slot as slot2'
                )
                ->get();

            if ($instructorConflicts->isNotEmpty()) {
                $conflicts['instructor'] = $instructorConflicts->toArray();
                Log::error("Found " . count($conflicts['instructor']) . " instructor conflicts in saved schedule!");
            }
        } catch (\Exception $e) {
            Log::error("Failed to validate saved schedule: " . $e->getMessage());
        }

        return $conflicts;
    }

    public function register(): void {}
    public function boot(): void {}
    public function __construct() {}
}
