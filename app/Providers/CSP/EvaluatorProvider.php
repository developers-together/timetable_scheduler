<?php

namespace App\Providers\CSP;

use Illuminate\Support\ServiceProvider;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\Log;

class EvaluatorProvider extends ServiceProvider
{
    private const PREFERRED_START = '10:45:00';
    private const PREFERRED_END = '14:00:00';
    private const GOOD_TIME_BONUS = 20;
    private const BAD_TIME_PENALTY = 0;
    private const HALF_SLOT_EFFICIENCY_BONUS = 5;  // Bonus for using half-slots efficiently

    public function evaluate(array $assignment, array $variables): int
    {
        Log::info("=== Evaluating Schedule Quality (with Slot Support) ===");

        $timeSlots = TimeSlot::all()->keyBy('id');

        $scores = [
            'time_preference' => 0,
            'instructor_balance' => 0,
            'room_utilization' => 0,
            'slot_efficiency' => 0,
        ];

        $scores['time_preference'] = $this->evaluateTimePreferences($assignment, $timeSlots);

        $scores['instructor_balance'] = $this->evaluateInstructorBalance($assignment, $variables);

        $scores['room_utilization'] = $this->evaluateRoomUtilization($assignment);

        $scores['slot_efficiency'] = $this->evaluateSlotEfficiency($assignment);

        $totalScore = array_sum($scores);

        Log::info("Quality Metrics:");
        Log::info("  - Time preference score: {$scores['time_preference']}");
        Log::info("  - Instructor balance score: {$scores['instructor_balance']}");
        Log::info("  - Room utilization score: {$scores['room_utilization']}");
        Log::info("  - Slot efficiency score: {$scores['slot_efficiency']}");
        Log::info("  - TOTAL SCORE: {$totalScore}");

        return $totalScore;
    }

    private function evaluateTimePreferences(array $assignment, $timeSlots): int
    {
        $score = 0;
        $goodSlots = 0;
        $badSlots = 0;

        foreach ($assignment as $value) {
            $slot = $timeSlots[$value['time_slot_id']] ?? null;

            if (!$slot) {
                Log::warning("Time slot {$value['time_slot_id']} not found");
                continue;
            }

            if ($slot->time >= self::PREFERRED_START && $slot->time <= self::PREFERRED_END) {
                $score += self::GOOD_TIME_BONUS;
                $goodSlots++;
            } else {
                $score += self::BAD_TIME_PENALTY;
                $badSlots++;
            }
        }

        Log::debug("Time preferences: {$goodSlots} good slots, {$badSlots} bad slots");

        return $score;
    }

    private function evaluateInstructorBalance(array $assignment, array $variables): int
    {
        $instructorLoads = [];

        foreach ($assignment as $varIndex => $value) {
            $instructorId = $variables[$varIndex]['instructor_id'] ?? null;

            if (!is_null($instructorId)) {
                $instructorLoads[$instructorId] = ($instructorLoads[$instructorId] ?? 0) + 1;
            }
        }

        if (empty($instructorLoads)) {
            return 0;
        }

        $mean = array_sum($instructorLoads) / count($instructorLoads);
        $variance = 0;

        foreach ($instructorLoads as $load) {
            $variance += pow($load - $mean, 2);
        }

        $stdDev = sqrt($variance / count($instructorLoads));

        $score = max(0, 50 - (int)($stdDev * 10));

        Log::debug("Instructor balance: mean={$mean}, stdDev={$stdDev}, score={$score}");

        $maxLoad = max($instructorLoads);
        $minLoad = min($instructorLoads);

        if ($maxLoad - $minLoad > 3) {
            Log::warning("Unbalanced instructor loads: max={$maxLoad}, min={$minLoad}");
        }

        return $score;
    }
    private function evaluateRoomUtilization(array $assignment): int
    {
        $roomSlotUsage = [];

        // Count how many times each room-slot combination is used
        foreach ($assignment as $value) {
            $key = $value['room_id'] . '-' . $value['time_slot_id'] . '-' . $value['slot'];
            $roomSlotUsage[$key] = ($roomSlotUsage[$key] ?? 0) + 1;
        }

        $uniqueRoomSlots = count($roomSlotUsage);
        $totalAssignments = count($assignment);

        if ($uniqueRoomSlots === 0) {
            return 0;
        }

        $utilizationRatio = $totalAssignments / $uniqueRoomSlots;

        $score = (int)($utilizationRatio * 20);

        Log::debug("Room utilization: {$totalAssignments} assignments in {$uniqueRoomSlots} room-time-slots, ratio={$utilizationRatio}");

        return $score;
    }

    private function evaluateSlotEfficiency(array $assignment): int
    {
        $score = 0;

        $roomTimeSlots = [];

        foreach ($assignment as $varIndex => $value) {
            $key = $value['room_id'] . '-' . $value['time_slot_id'];
            $slot = $value['slot'];

            if (!isset($roomTimeSlots[$key])) {
                $roomTimeSlots[$key] = [
                    'full' => 0,
                    'first_half' => 0,
                    'second_half' => 0,
                ];
            }

            $roomTimeSlots[$key][$slot]++;
        }

        $perfectPairings = 0;
        $wastedHalfSlots = 0;

        foreach ($roomTimeSlots as $key => $slots) {

            if ($slots['first_half'] > 0 && $slots['second_half'] > 0) {
                $pairs = min($slots['first_half'], $slots['second_half']);
                $score += $pairs * self::HALF_SLOT_EFFICIENCY_BONUS;
                $perfectPairings += $pairs;
            }

            $unpaired = abs($slots['first_half'] - $slots['second_half']);
            if ($unpaired > 0) {
                $wastedHalfSlots += $unpaired;
            }
        }

        Log::debug("Slot efficiency: {$perfectPairings} perfect pairings, {$wastedHalfSlots} wasted half-slots");

        $slotDistribution = [
            'full' => 0,
            'first_half' => 0,
            'second_half' => 0,
        ];

        foreach ($assignment as $value) {
            $slotDistribution[$value['slot']]++;
        }

        Log::debug("Slot distribution: full={$slotDistribution['full']}, " .
            "first_half={$slotDistribution['first_half']}, " .
            "second_half={$slotDistribution['second_half']}");

        return $score;
    }
    public function generateReport(array $assignment, array $variables): array
    {
        $timeSlots = TimeSlot::all()->keyBy('id');

        $report = [
            'total_score' => $this->evaluate($assignment, $variables),
            'metrics' => [],
            'warnings' => [],
            'statistics' => [],
        ];

        $timeDistribution = [];
        foreach ($assignment as $value) {
            $slotId = $value['time_slot_id'];
            $timeDistribution[$slotId] = ($timeDistribution[$slotId] ?? 0) + 1;
        }

        $report['statistics']['time_distribution'] = $timeDistribution;

        $roomDistribution = [];
        foreach ($assignment as $value) {
            $roomId = $value['room_id'];
            $roomDistribution[$roomId] = ($roomDistribution[$roomId] ?? 0) + 1;
        }

        $report['statistics']['room_distribution'] = $roomDistribution;

        $slotDistribution = [
            'full' => 0,
            'first_half' => 0,
            'second_half' => 0,
        ];

        foreach ($assignment as $value) {
            $slotDistribution[$value['slot']]++;
        }

        $report['statistics']['slot_distribution'] = $slotDistribution;

        $instructorWorkload = [];
        foreach ($assignment as $varIndex => $value) {
            $instructorId = $variables[$varIndex]['instructor_id'] ?? 'unassigned';
            $instructorWorkload[$instructorId] = ($instructorWorkload[$instructorId] ?? 0) + 1;
        }

        $report['statistics']['instructor_workload'] = $instructorWorkload;

        $pairingAnalysis = $this->analyzeHalfSlotPairings($assignment);
        $report['statistics']['half_slot_pairings'] = $pairingAnalysis;

        return $report;
    }
    private function analyzeHalfSlotPairings(array $assignment): array
    {
        $roomTimeSlots = [];

        foreach ($assignment as $varIndex => $value) {
            $key = $value['room_id'] . '-' . $value['time_slot_id'];
            $slot = $value['slot'];

            if (!isset($roomTimeSlots[$key])) {
                $roomTimeSlots[$key] = [
                    'room_id' => $value['room_id'],
                    'time_slot_id' => $value['time_slot_id'],
                    'full' => 0,
                    'first_half' => 0,
                    'second_half' => 0,
                ];
            }

            $roomTimeSlots[$key][$slot]++;
        }

        $analysis = [
            'perfect_pairings' => 0,
            'unpaired_first_half' => 0,
            'unpaired_second_half' => 0,
            'full_slots_used' => 0,
        ];

        foreach ($roomTimeSlots as $data) {
            $analysis['full_slots_used'] += $data['full'];

            $pairs = min($data['first_half'], $data['second_half']);
            $analysis['perfect_pairings'] += $pairs;

            $analysis['unpaired_first_half'] += max(0, $data['first_half'] - $pairs);
            $analysis['unpaired_second_half'] += max(0, $data['second_half'] - $pairs);
        }

        return $analysis;
    }

    public function register(): void {}
    public function boot(): void {}
    public function __construct() {}
}
