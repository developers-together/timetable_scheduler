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
    private const BAD_TIME_PENALTY = -10;
    private const HALF_SLOT_EFFICIENCY_BONUS = 5;  // Bonus for using half-slots efficiently

    /**
     * Evaluate the quality of a schedule assignment (with slot awareness)
     * Higher score = better schedule
     */
    public function evaluate(array $assignment, array $variables): int
    {
        Log::info("=== Evaluating Schedule Quality (with Slot Support) ===");

        $timeSlots = TimeSlot::all()->keyBy('id');

        $scores = [
            'time_preference' => 0,
            'instructor_balance' => 0,
            'room_utilization' => 0,
            'slot_efficiency' => 0,  // NEW: reward efficient half-slot usage
        ];

        // Metric 1: Time slot preferences
        $scores['time_preference'] = $this->evaluateTimePreferences($assignment, $timeSlots);

        // Metric 2: Instructor workload balance
        $scores['instructor_balance'] = $this->evaluateInstructorBalance($assignment, $variables);

        // Metric 3: Room utilization efficiency
        $scores['room_utilization'] = $this->evaluateRoomUtilization($assignment);

        // Metric 4: Slot efficiency (NEW)
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

    /**
     * Evaluate time slot preferences
     * Prefer slots between 9:00 AM and 2:15 PM
     */
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

    /**
     * Evaluate instructor workload balance
     * Penalize uneven distribution of classes across instructors
     */
    private function evaluateInstructorBalance(array $assignment, array $variables): int
    {
        $instructorLoads = [];

        // Count assignments per instructor
        foreach ($assignment as $varIndex => $value) {
            $instructorId = $variables[$varIndex]['instructor_id'] ?? null;

            if (!is_null($instructorId)) {
                $instructorLoads[$instructorId] = ($instructorLoads[$instructorId] ?? 0) + 1;
            }
        }

        if (empty($instructorLoads)) {
            return 0;
        }

        // Calculate standard deviation of workload
        $mean = array_sum($instructorLoads) / count($instructorLoads);
        $variance = 0;

        foreach ($instructorLoads as $load) {
            $variance += pow($load - $mean, 2);
        }

        $stdDev = sqrt($variance / count($instructorLoads));

        // Lower std dev = better balance = higher score
        // Normalize: perfect balance (stdDev=0) = +50 points
        $score = max(0, 50 - (int)($stdDev * 10));

        Log::debug("Instructor balance: mean={$mean}, stdDev={$stdDev}, score={$score}");

        // Log instructors with extreme loads
        $maxLoad = max($instructorLoads);
        $minLoad = min($instructorLoads);

        if ($maxLoad - $minLoad > 3) {
            Log::warning("Unbalanced instructor loads: max={$maxLoad}, min={$minLoad}");
        }

        return $score;
    }

    /**
     * Evaluate room utilization efficiency
     * Prefer compact schedules that don't waste room capacity
     */
    private function evaluateRoomUtilization(array $assignment): int
    {
        $roomSlotUsage = [];

        // Count how many times each room-slot combination is used
        foreach ($assignment as $value) {
            $key = $value['room_id'] . '-' . $value['time_slot_id'] . '-' . $value['slot'];
            $roomSlotUsage[$key] = ($roomSlotUsage[$key] ?? 0) + 1;
        }

        // Bonus for high utilization (more classes packed into fewer room-slot pairs)
        $uniqueRoomSlots = count($roomSlotUsage);
        $totalAssignments = count($assignment);

        if ($uniqueRoomSlots === 0) {
            return 0;
        }

        $utilizationRatio = $totalAssignments / $uniqueRoomSlots;

        // Higher ratio = better utilization (should be exactly 1 due to constraints)
        $score = (int)($utilizationRatio * 20);

        Log::debug("Room utilization: {$totalAssignments} assignments in {$uniqueRoomSlots} room-time-slots, ratio={$utilizationRatio}");

        return $score;
    }

    /**
     * Evaluate slot efficiency (NEW)
     * Reward efficient use of half-slots (pairing tutorials in same time slot)
     */
    private function evaluateSlotEfficiency(array $assignment): int
    {
        $score = 0;

        // Group assignments by room and time slot
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

        // Analyze each room-time combination
        foreach ($roomTimeSlots as $key => $slots) {
            // Perfect pairing: first_half + second_half = efficient use
            if ($slots['first_half'] > 0 && $slots['second_half'] > 0) {
                $pairs = min($slots['first_half'], $slots['second_half']);
                $score += $pairs * self::HALF_SLOT_EFFICIENCY_BONUS;
                $perfectPairings += $pairs;
            }

            // Wasted half-slots: unpaired halves
            $unpaired = abs($slots['first_half'] - $slots['second_half']);
            if ($unpaired > 0) {
                $wastedHalfSlots += $unpaired;
            }
        }

        Log::debug("Slot efficiency: {$perfectPairings} perfect pairings, {$wastedHalfSlots} wasted half-slots");

        // Count slot type distribution
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

    /**
     * Generate detailed report of schedule quality
     */
    public function generateReport(array $assignment, array $variables): array
    {
        $timeSlots = TimeSlot::all()->keyBy('id');

        $report = [
            'total_score' => $this->evaluate($assignment, $variables),
            'metrics' => [],
            'warnings' => [],
            'statistics' => [],
        ];

        // Time distribution
        $timeDistribution = [];
        foreach ($assignment as $value) {
            $slotId = $value['time_slot_id'];
            $timeDistribution[$slotId] = ($timeDistribution[$slotId] ?? 0) + 1;
        }

        $report['statistics']['time_distribution'] = $timeDistribution;

        // Room distribution
        $roomDistribution = [];
        foreach ($assignment as $value) {
            $roomId = $value['room_id'];
            $roomDistribution[$roomId] = ($roomDistribution[$roomId] ?? 0) + 1;
        }

        $report['statistics']['room_distribution'] = $roomDistribution;

        // Slot distribution (NEW)
        $slotDistribution = [
            'full' => 0,
            'first_half' => 0,
            'second_half' => 0,
        ];

        foreach ($assignment as $value) {
            $slotDistribution[$value['slot']]++;
        }

        $report['statistics']['slot_distribution'] = $slotDistribution;

        // Instructor workload
        $instructorWorkload = [];
        foreach ($assignment as $varIndex => $value) {
            $instructorId = $variables[$varIndex]['instructor_id'] ?? 'unassigned';
            $instructorWorkload[$instructorId] = ($instructorWorkload[$instructorId] ?? 0) + 1;
        }

        $report['statistics']['instructor_workload'] = $instructorWorkload;

        // Half-slot pairing analysis (NEW)
        $pairingAnalysis = $this->analyzeHalfSlotPairings($assignment);
        $report['statistics']['half_slot_pairings'] = $pairingAnalysis;

        return $report;
    }

    /**
     * Analyze half-slot pairings for detailed reporting
     */
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
