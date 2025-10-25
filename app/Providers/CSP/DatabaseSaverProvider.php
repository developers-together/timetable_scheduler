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
                        'slot' => $slot,  // NOW PROPERLY USING THE SLOT VALUE
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

    /**
     * Check for room conflicts in saved schedule
     */
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
