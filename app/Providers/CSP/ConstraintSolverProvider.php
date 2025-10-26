<?php

namespace App\Providers\CSP;

use Illuminate\Support\ServiceProvider;
use SplQueue;
use Illuminate\Support\Facades\Log;

class ConstraintSolverProvider extends ServiceProvider
{
    private array $domains;
    private array $neighbors;
    private array $variables;
    private array $assignments = [];

    private int $backtrackCalls = 0;
    private int $constraintChecks = 0;
    private int $domainWipeouts = 0;

    private int $maxBacktrackCalls = 20000;
    private int $maxConstraintChecks = 20000000;
    private int $consecutiveFailures = 100;
    private int $lastFailedVar = -1;

    // Performance profiling
    private float $timeInConsistencyChecks = 0;
    private float $timeInForwardChecking = 0;
    private float $timeInVariableSelection = 0;

    public function solve(array &$domains, array &$neighbors, array &$variables): ?array
    {
        set_time_limit(180);
        ini_set('memory_limit', '1024M');

        $this->domains = &$domains;
        $this->neighbors = &$neighbors;
        $this->variables = &$variables;
        $this->assignments = [];
        $this->resetStatistics();

        Log::info("=== CSP Solver (Optimized with Profiling) ===");
        Log::info("Variables: " . count($variables));
        Log::info("Initial domains: " . array_sum(array_map('count', $domains)));
        Log::info("Edges in constraint graph: " . (array_sum(array_map('count', $neighbors)) / 2));

        // Calculate graph density
        $numVars = count($variables);
        $numEdges = array_sum(array_map('count', $neighbors)) / 2;
        $possibleEdges = ($numVars * ($numVars - 1)) / 2;
        $density = $possibleEdges > 0 ? round(($numEdges / $possibleEdges) * 100, 2) : 0;
        Log::info("Graph density: {$density}%");

        if (count($variables) < 50) {
            Log::info("Skipping AC3 for small problem");
        } else {
            $startTime = microtime(true);
            if (!$this->ac3($this->domains, $this->neighbors)) {
                Log::error("AC3 failed - problem unsolvable");
                return null;
            }
            $ac3Time = microtime(true) - $startTime;
            Log::info("AC3 complete: " . round($ac3Time, 2) . "s");
            Log::info("Domains after AC3: " . array_sum(array_map('count', $domains)));
        }

        $startTime = microtime(true);
        $result = $this->backtrack($this->domains, $this->neighbors);

        if ($result === null) {
            Log::error("No solution found");
            $this->logStatistics();
            return null;
        }

        $solveTime = microtime(true) - $startTime;

        Log::info("=== Solution Found ===");
        Log::info("Backtracking time: " . round($solveTime, 2) . "s");
        $this->logStatistics();
        $this->logPerformanceProfile();

        return $result;
    }

    public function ac3(array &$domains, array &$neighbors): bool
    {
        $queue = new SplQueue();

        foreach ($neighbors as $xi => $neighborList) {
            foreach ($neighborList as $xj) {
                $queue->enqueue([$xi, $xj]);
            }
        }

        $iterations = 0;
        $maxIterations = 50000;

        while (!$queue->isEmpty() && $iterations++ < $maxIterations) {
            [$xi, $xj] = $queue->dequeue();

            if ($this->revise($xi, $xj, $domains)) {
                if (empty($domains[$xi])) {
                    return false;
                }

                foreach ($neighbors[$xi] as $xk) {
                    if ($xk !== $xj) {
                        $queue->enqueue([$xk, $xi]);
                    }
                }
            }
        }

        return true;
    }

    private function revise(int $xi, int $xj, array &$domains): bool
    {
        $revised = false;
        $newDomain = [];

        foreach ($domains[$xi] as $valueI) {
            $hasSupport = false;

            foreach ($domains[$xj] as $valueJ) {
                if ($this->isConsistent($xi, $xj, $valueI, $valueJ)) {
                    $hasSupport = true;
                    break;
                }
            }

            if ($hasSupport) {
                $newDomain[] = $valueI;
            } else {
                $revised = true;
            }
        }

        $domains[$xi] = $newDomain;
        return $revised;
    }

    private function isConsistent(int $xi, int $xj, array $valueI, array $valueJ): bool
    {
        $startTime = microtime(true);

        // $this->constraintChecks++;

        if (
            $this->constraintChecks % 10000 === 0 &&
            $this->constraintChecks > $this->maxConstraintChecks
        ) {
            throw new \RuntimeException("Too many constraint checks - over-constrained");
        }

        if ($xi === $xj) {
            $this->timeInConsistencyChecks += microtime(true) - $startTime;
            return true;
        }

        $timeI = $valueI['time_slot_id'];
        $timeJ = $valueJ['time_slot_id'];

        // OPTIMIZATION: If different time slots, no conflict possible
        if ($timeI !== $timeJ) {
            $this->timeInConsistencyChecks += microtime(true) - $startTime;
            return true;
        }

        // Same time slot - check room conflict
        $roomI = $valueI['room_id'];
        $roomJ = $valueJ['room_id'];

        if ($roomI === $roomJ) {
            $slotI = $valueI['slot'];
            $slotJ = $valueJ['slot'];

            if ($slotI === 'full' || $slotJ === 'full' || $slotI === $slotJ) {
                $this->timeInConsistencyChecks += microtime(true) - $startTime;
                return false;
            }
        }

        // Check instructor conflict
        $instructorI = $this->variables[$xi]['instructor_id'] ?? null;
        $instructorJ = $this->variables[$xj]['instructor_id'] ?? null;

        if ($instructorI !== null && $instructorJ !== null && $instructorI === $instructorJ) {
            $slotI = $valueI['slot'];
            $slotJ = $valueJ['slot'];

            if ($slotI === 'full' || $slotJ === 'full' || $slotI === $slotJ) {
                $this->timeInConsistencyChecks += microtime(true) - $startTime;
                return false;
            }
        }

        $this->timeInConsistencyChecks += microtime(true) - $startTime;
        return true;
    }

    public function backtrack(array $domains, array $neighbors, array $assignment = []): ?array
    {
        $this->backtrackCalls++;

        if ($this->backtrackCalls >= $this->maxBacktrackCalls) {
            Log::error("Max backtrack calls ({$this->maxBacktrackCalls}) exceeded");
            return null;
        }

        if ($this->backtrackCalls % 100 === 0) {
            $progress = count($assignment) . "/" . count($domains);
            Log::info("Backtrack #{$this->backtrackCalls}: {$progress} assigned");
        }

        if (count($assignment) === count($domains)) {
            $this->assignments = $assignment;
            return $assignment;
        }

        // Variable selection with profiling
        $selectStart = microtime(true);
        $var = $this->selectUnassignedVariable($domains, $assignment);
        $this->timeInVariableSelection += microtime(true) - $selectStart;

        if ($var === null) {
            return null;
        }

        $orderedValues = $domains[$var];

        if (empty($orderedValues)) {
            $this->domainWipeouts++;

            if ($var === $this->lastFailedVar) {
                $this->consecutiveFailures++;

                if ($this->consecutiveFailures > 30) {
                    Log::error("Variable {$var} failed 30+ times - aborting");
                    $this->diagnoseVariable($var, $assignment);
                    return null;
                }
            } else {
                $this->consecutiveFailures = 1;
                $this->lastFailedVar = $var;
            }

            return null;
        }

        if ($var !== $this->lastFailedVar) {
            $this->consecutiveFailures = 0;
        }

        foreach ($orderedValues as $value) {
            if ($this->isConsistentWithAssignment($var, $value, $assignment)) {
                $assignment[$var] = $value;

                $domainsCopy = $domains;

                // Forward checking with profiling
                $fcStart = microtime(true);
                $fcResult = $this->forwardCheck($var, $value, $domainsCopy, $neighbors, $assignment);
                $this->timeInForwardChecking += microtime(true) - $fcStart;

                if ($fcResult) {
                    $result = $this->backtrack($domainsCopy, $neighbors, $assignment);

                    if ($result !== null) {
                        return $result;
                    }
                } else {
                    $this->domainWipeouts++;
                }

                unset($assignment[$var]);
            }
        }

        return null;
    }

    private function selectUnassignedVariable(array $domains, array $assignment): ?int
    {
        $minSize = PHP_INT_MAX;
        $selectedVar = null;

        foreach ($domains as $var => $domain) {
            if (isset($assignment[$var])) {
                continue;
            }

            $domainSize = count($domain);

            if ($domainSize < $minSize) {
                $minSize = $domainSize;
                $selectedVar = $var;
            }
        }

        return $selectedVar;
    }

    private function isConsistentWithAssignment(int $var, array $value, array $assignment): bool
    {
        foreach ($assignment as $assignedVar => $assignedValue) {
            if (!$this->isConsistent($var, $assignedVar, $value, $assignedValue)) {
                return false;
            }
        }
        return true;
    }

    private function forwardCheck(int $var, array $value, array &$domains, array $neighbors, array $assignment): bool
    {
        // OPTIMIZATION: Only check neighbors that share rooms or instructors
        foreach ($neighbors[$var] as $neighbor) {
            if (isset($assignment[$neighbor])) {
                continue;
            }

            $newDomain = [];
            foreach ($domains[$neighbor] as $neighborValue) {
                if ($this->isConsistent($var, $neighbor, $value, $neighborValue)) {
                    $newDomain[] = $neighborValue;
                }
            }

            if (empty($newDomain)) {
                return false;
            }

            $domains[$neighbor] = $newDomain;
        }

        return true;
    }

    private function diagnoseVariable(int $var, array $assignment): void
    {
        $varInfo = $this->variables[$var];
        Log::error("=== PROBLEM VARIABLE {$var} ===");
        Log::error("Course: {$varInfo['course_name']}");
        Log::error("Type: {$varInfo['type']}");
        Log::error("Instructor: {$varInfo['instructor_id']}");
        Log::error("Progress: " . count($assignment) . "/" . count($this->domains));
    }

    private function resetStatistics(): void
    {
        $this->backtrackCalls = 0;
        $this->constraintChecks = 0;
        $this->domainWipeouts = 0;
        $this->consecutiveFailures = 0;
        $this->lastFailedVar = -1;

        $this->timeInConsistencyChecks = 0;
        $this->timeInForwardChecking = 0;
        $this->timeInVariableSelection = 0;
    }

    private function logStatistics(): void
    {
        Log::info("=== Statistics ===");
        Log::info("Backtracks: {$this->backtrackCalls}");
        Log::info("Constraint checks: " . number_format($this->constraintChecks));
        Log::info("Domain wipeouts: {$this->domainWipeouts}");

        if ($this->backtrackCalls > 0) {
            $checksPerBacktrack = round($this->constraintChecks / $this->backtrackCalls, 2);
            Log::info("Avg checks per backtrack: {$checksPerBacktrack}");
        }
    }

    private function logPerformanceProfile(): void
    {
        Log::info("=== Performance Profile ===");

        $totalProfiledTime = $this->timeInConsistencyChecks +
            $this->timeInForwardChecking +
            $this->timeInVariableSelection;

        if ($totalProfiledTime > 0) {
            $consistencyPercent = round(($this->timeInConsistencyChecks / $totalProfiledTime) * 100, 1);
            $forwardCheckPercent = round(($this->timeInForwardChecking / $totalProfiledTime) * 100, 1);
            $varSelectPercent = round(($this->timeInVariableSelection / $totalProfiledTime) * 100, 1);

            Log::info("Time breakdown:");
            Log::info("  - Consistency checks: " . round($this->timeInConsistencyChecks, 2) . "s ({$consistencyPercent}%)");
            Log::info("  - Forward checking: " . round($this->timeInForwardChecking, 2) . "s ({$forwardCheckPercent}%)");
            Log::info("  - Variable selection: " . round($this->timeInVariableSelection, 2) . "s ({$varSelectPercent}%)");

            // Performance recommendations
            if ($consistencyPercent > 60) {
                Log::warning("⚠ Consistency checks dominate runtime ({$consistencyPercent}%)");
                Log::warning("Recommendation: Reduce graph density or simplify constraint checks");
            }

            if ($forwardCheckPercent > 30) {
                Log::warning("⚠ Forward checking is expensive ({$forwardCheckPercent}%)");
                Log::warning("Recommendation: Consider limiting forward checking depth");
            }

            if ($this->constraintChecks > 1000000) {
                Log::warning("⚠ Very high constraint check count: " . number_format($this->constraintChecks));
                Log::warning("Recommendation: Improve AC3 preprocessing or reduce domain sizes");
            }
        }
    }

    public function getResults(): array
    {
        return $this->assignments;
    }

    public function register(): void {}
    public function boot(): void {}
    public function __construct() {}
}
