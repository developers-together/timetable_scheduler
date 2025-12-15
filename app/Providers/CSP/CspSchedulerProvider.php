<?php

namespace App\Providers\CSP;

use Illuminate\Support\ServiceProvider;
use App\Providers\CSP\VariableManagerProvider as VariableManager;
use App\Providers\CSP\ConstraintSolverProvider as ConstraintSolver;
use App\Providers\CSP\EvaluatorProvider as Evaluator;
use App\Providers\CSP\DatabaseSaverProvider as DatabaseSaver;
use App\Providers\CSP\ParallelSolverProvider as ParallelSolver;
use Illuminate\Support\Facades\Log;

class CspSchedulerProvider extends ServiceProvider
{
    private VariableManager $varManager;
    private ConstraintSolver $solver;
    private Evaluator $evaluator;
    private DatabaseSaver $dbSaver;
    private bool $useParallel = true; // Set to true to use parallel solving

    public function __construct(bool $parallel = true)
    {
        // Components will be initialized in generateSchedule to respect memory limits

        ini_set('memory_limit', '4069M');
        set_time_limit(300);

        // Initialize components here to ensure memory limit applies
        $this->varManager = new VariableManager();
        $this->solver = new ConstraintSolver();
        $this->evaluator = new Evaluator();
        $this->dbSaver = new DatabaseSaver();
        $this->useParallel = $parallel;
    }

    public function generateSchedule(): array
    {
 

        try {
            Log::info("========================================");
            Log::info("=== Starting Schedule Generation ===");
            Log::info("========================================");

            $overallStart = microtime(true);

            $this->validateInputs();

            $variables = $this->varManager->getVariables();
            $domains = $this->varManager->getDomains();
            $neighbors = $this->varManager->getNeighbors();

            $this->dbSaver->resetDB();
            Log::info("Database reset complete");

            // Choose solving method
            if ($this->useParallel) {
                Log::info("Using PARALLEL solver with multiple processes");
                $parallelSolver = new ParallelSolver(4); // 4 workers
                $assignment = $parallelSolver->parallelSolve($domains, $neighbors, $variables);
            } else {
                $assignment = $this->solver->solve($domains, $neighbors, $variables);
            }

            if (!$assignment) {
                // Run diagnostics before throwing exception
                $this->diagnoseFailure($variables, $domains, $neighbors);
                throw new \Exception("No valid timetable found! The problem may be over-constrained. Check logs for diagnostics.");
            }

            $score = $this->evaluator->evaluate($assignment, $variables);
            Log::info("Schedule quality score: {$score}");

            $this->logSolution($assignment, $variables);

            $saveStart = microtime(true);
            $this->dbSaver->saveOnDb($assignment, $score, $variables);
            $saveTime = microtime(true) - $saveStart;
            Log::info("Saved to database in " . round($saveTime, 3) . "s");

            $totalTime = microtime(true) - $overallStart;

            Log::info("========================================");
            Log::info("=== Schedule Generation Complete ===");
            Log::info("Total time: " . round($totalTime, 3) . "s");
            Log::info("========================================");

            return [
                'success' => true,
                'assignment' => $assignment,
                'score' => $score,
                'statistics' => [
                    'total_time' => round($totalTime, 3),
                    'variables_count' => count($variables),
                    'assignments_count' => count($assignment),
                ],
            ];
        } catch (\Exception $e) {
            Log::error("Schedule generation failed: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());

            throw $e;
        }
    }
    
    /**
     * Diagnose why no solution was found
     */
    private function diagnoseFailure(array $variables, array $domains, array $neighbors): void
    {
        Log::error("========================================");
        Log::error("=== DIAGNOSTIC REPORT: NO SOLUTION FOUND ===");
        Log::error("========================================");
        
        // 1. Capacity Analysis
        Log::error("\n=== 1. CAPACITY ANALYSIS ===");
        $totalVars = count($variables);
        $timeSlotCount = \App\Models\TimeSlot::count();
        $roomCount = \App\Models\Room::count();
        $maxPossibleSlots = $timeSlotCount * $roomCount;
        
        Log::error("Total variables to schedule: {$totalVars}");
        Log::error("Time slots available: {$timeSlotCount}");
        Log::error("Rooms available: {$roomCount}");
        Log::error("Max possible assignments (timeSlots × rooms): {$maxPossibleSlots}");
        
        if ($totalVars > $maxPossibleSlots) {
            Log::error("❌ CRITICAL: More variables ({$totalVars}) than available slots ({$maxPossibleSlots})!");
        } else {
            $utilizationPercent = round(($totalVars / $maxPossibleSlots) * 100, 1);
            Log::error("Theoretical capacity utilization: {$utilizationPercent}%");
            if ($utilizationPercent > 80) {
                Log::error("⚠️ WARNING: High utilization (>80%) makes scheduling very difficult");
            }
        }
        
        // 2. Variable breakdown by type
        Log::error("\n=== 2. VARIABLES BY TYPE ===");
        $byType = [];
        $byFacultyYear = [];
        $lecturesByFacultyYear = [];
        foreach ($variables as $var) {
            $type = $var['type'];
            $byType[$type] = ($byType[$type] ?? 0) + 1;
            
            $key = ($var['faculty'] ?? 'Unknown') . ' Year ' . ($var['year'] ?? '?');
            $byFacultyYear[$key] = ($byFacultyYear[$key] ?? 0) + 1;
            
            // Track lectures separately - they're the bottleneck
            if ($type === 'Lecture') {
                $lecturesByFacultyYear[$key] = ($lecturesByFacultyYear[$key] ?? 0) + 1;
            }
        }
        
        foreach ($byType as $type => $count) {
            Log::error("  {$type}: {$count} sessions");
        }
        
        Log::error("\n=== 3. LECTURES BY FACULTY/YEAR (These must not overlap!) ===");
        foreach ($lecturesByFacultyYear as $key => $count) {
            Log::error("  {$key}: {$count} lectures");
            if ($count > $timeSlotCount) {
                Log::error("    ❌ CRITICAL: More lectures ({$count}) than time slots ({$timeSlotCount}) for {$key}!");
            }
        }
        
        Log::error("\n=== 4. TOTAL SESSIONS BY FACULTY/YEAR (Labs/Tutorials can overlap across sections) ===");
        foreach ($byFacultyYear as $key => $count) {
            $lectureCount = $lecturesByFacultyYear[$key] ?? 0;
            Log::error("  {$key}: {$count} total ({$lectureCount} lectures)");
        }
        
        // 3. Domain size analysis (find problem variables)
        Log::error("\n=== 5. PROBLEM VARIABLES (Small Domains) ===");
        $domainSizes = [];
        foreach ($domains as $varIndex => $domain) {
            $domainSizes[$varIndex] = count($domain);
        }
        asort($domainSizes);
        
        $problemVars = array_slice($domainSizes, 0, 10, true);
        foreach ($problemVars as $varIndex => $size) {
            $var = $variables[$varIndex];
            $neighborCount = count($neighbors[$varIndex] ?? []);
            Log::error("  Var {$varIndex}: {$var['course_name']} ({$var['type']}) - Domain: {$size}, Neighbors: {$neighborCount}");
            Log::error("    Faculty: {$var['faculty']}, Year: {$var['year']}, Group: {$var['groupNO']}");
        }
        
        // 4. High-conflict variables (most neighbors)
        Log::error("\n=== 6. HIGH-CONFLICT VARIABLES (Most Neighbors) ===");
        $neighborCounts = [];
        foreach ($neighbors as $varIndex => $neighborList) {
            $neighborCounts[$varIndex] = count($neighborList);
        }
        arsort($neighborCounts);
        
        $highConflict = array_slice($neighborCounts, 0, 10, true);
        foreach ($highConflict as $varIndex => $count) {
            $var = $variables[$varIndex];
            $domainSize = count($domains[$varIndex] ?? []);
            Log::error("  Var {$varIndex}: {$var['course_name']} ({$var['type']}) - Neighbors: {$count}, Domain: {$domainSize}");
        }
        
        // 5. Room type availability
        Log::error("\n=== 7. ROOM TYPE AVAILABILITY ===");
        $roomsByType = \App\Models\Room::selectRaw('type, COUNT(*) as cnt')->groupBy('type')->pluck('cnt', 'type');
        
        $lectureVars = $byType['Lecture'] ?? 0;
        $labVars = $byType['Lab'] ?? 0;
        $tutorialVars = $byType['Tutorial'] ?? 0;
        
        $lectureRooms = ($roomsByType['Classroom'] ?? 0) + ($roomsByType['Theater'] ?? 0) + ($roomsByType['Hall'] ?? 0);
        $labRooms = ($roomsByType['ComputerLab'] ?? 0) + ($roomsByType['BioLab'] ?? 0) + 
                   ($roomsByType['PhysicsLab'] ?? 0) + ($roomsByType['DrawingLab'] ?? 0) +
                   ($roomsByType['DrawingStudio'] ?? 0) + ($roomsByType['Classroom'] ?? 0);
        $tutorialRooms = $roomsByType['Classroom'] ?? 0;
        
        Log::error("Lectures needed: {$lectureVars}, Suitable rooms: {$lectureRooms}, Capacity: " . ($lectureRooms * $timeSlotCount));
        Log::error("Labs needed: {$labVars}, Suitable rooms: {$labRooms}, Capacity: " . ($labRooms * $timeSlotCount));
        Log::error("Tutorials needed: {$tutorialVars}, Suitable rooms: {$tutorialRooms}, Capacity: " . ($tutorialRooms * $timeSlotCount * 2));
        
        // Check for bottlenecks
        if ($lectureVars > $lectureRooms * $timeSlotCount) {
            Log::error("❌ BOTTLENECK: Not enough lecture room capacity!");
        }
        if ($labVars > $labRooms * $timeSlotCount) {
            Log::error("❌ BOTTLENECK: Not enough lab room capacity!");
        }
        
        // 6. Instructor analysis
        Log::error("\n=== 8. INSTRUCTOR WORKLOAD ===");
        $instructorVars = [];
        foreach ($variables as $varIndex => $var) {
            $instId = $var['instructor_id'] ?? 'none';
            if ($instId !== 'none' && $instId !== null) {
                $instructorVars[$instId] = ($instructorVars[$instId] ?? 0) + 1;
            }
        }
        arsort($instructorVars);
        
        foreach (array_slice($instructorVars, 0, 5, true) as $instId => $count) {
            if ($count > $timeSlotCount) {
                Log::error("❌ Instructor {$instId}: {$count} sessions (EXCEEDS {$timeSlotCount} time slots!)");
            } else {
                Log::error("  Instructor {$instId}: {$count} sessions");
            }
        }
        
        Log::error("\n=== RECOMMENDATIONS ===");
        Log::error("1. Check if any Faculty/Year group has more sessions than time slots");
        Log::error("2. Check if any instructor is assigned more sessions than available time slots");
        Log::error("3. Verify room types match course requirements");
        Log::error("4. Consider reducing required capacity or adding more time slots/rooms");
        Log::error("========================================");
    }
    private function validateInputs(): void
    {
        $variables = $this->varManager->getVariables();
        $domains = $this->varManager->getDomains();
        $neighbors = $this->varManager->getNeighbors();

        if (empty($variables)) {
            throw new \Exception("No variables found! Check if required courses exist in database.");
        }

        if (empty($domains)) {
            throw new \Exception("No domains found! Check if rooms and time slots exist in database.");
        }

        if (empty($neighbors)) {
            throw new \Exception("No neighbor relationships found!");
        }

        $emptyDomainCount = 0;
        foreach ($domains as $varIndex => $domain) {
            if (empty($domain)) {
                $varInfo = $variables[$varIndex];
                Log::error(
                    "Variable {$varIndex} has empty domain: " .
                        "Course: {$varInfo['course_name']}, Type: {$varInfo['type']}"
                );
                $emptyDomainCount++;
            }
        }

        if ($emptyDomainCount > 0) {
            throw new \Exception(
                "{$emptyDomainCount} variables have empty domains! " .
                    "Check if suitable rooms exist for all course types."
            );
        }

        Log::info("Input validation passed");
    }
    private function logSolution(array $assignment, array $variables): void
    {
        Log::info("=== Solution Details ===");
        Log::info("Assigned {" . count($assignment) . "} out of {" . count($variables) . "} variables");

        $byType = [];
        foreach ($assignment as $varIndex => $value) {
            $type = $variables[$varIndex]['type'];
            $byType[$type] = ($byType[$type] ?? 0) + 1;
        }

        foreach ($byType as $type => $count) {
            Log::info("  - {$type}: {$count} assignments");
        }

        Log::debug("Sample assignments:");
        $sampleCount = min(5, count($assignment));
        $samples = array_slice($assignment, 0, $sampleCount, true);

        foreach ($samples as $varIndex => $value) {
            $var = $variables[$varIndex];
            Log::debug(
                "  Var {$varIndex}: {$var['course_name']} ({$var['type']}) -> " .
                    "Room: {$value['room_id']}, Time: {$value['time_slot_id']}"
            );
        }
    }

    public function register(): void {}
    public function boot(): void {}
}
