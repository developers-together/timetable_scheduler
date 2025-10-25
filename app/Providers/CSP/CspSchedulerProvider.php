<?php

namespace App\Providers\CSP;

use Illuminate\Support\ServiceProvider;
use App\Providers\CSP\VariableManagerProvider as VariableManager;
use App\Providers\CSP\ConstraintSolverProvider as ConstraintSolver;
use App\Providers\CSP\EvaluatorProvider as Evaluator;
use App\Providers\CSP\DatabaseSaverProvider as DatabaseSaver;
use Illuminate\Support\Facades\Log;

class CspSchedulerProvider extends ServiceProvider
{
    private VariableManager $varManager;
    private ConstraintSolver $solver;
    private Evaluator $evaluator;
    private DatabaseSaver $dbSaver;

    public function __construct()
    {
        $this->varManager = new VariableManager();
        $this->solver = new ConstraintSolver();
        $this->evaluator = new Evaluator();
        $this->dbSaver = new DatabaseSaver();
    }

    public function generateSchedule(): array
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(300);
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

            $assignment = $this->solver->solve($domains, $neighbors, $variables);

            if (!$assignment) {
                throw new \Exception("No valid timetable found! The problem may be over-constrained.");
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
