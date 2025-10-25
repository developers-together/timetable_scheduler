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

    // Statistics
    private int $backtrackCalls = 0;
    private int $constraintChecks = 0;
    private int $domainWipeouts = 0;

    // CRITICAL: Safety limits
    private int $maxBacktrackCalls = 20000;      // More attempts
    private int $maxConstraintChecks = 20000000;  // 20M checks
    private int $consecutiveFailures = 100;       // Patient
    private int $lastFailedVar = -1;

    public function solve(array &$domains, array &$neighbors, array &$variables): ?array
    {
        // CRITICAL: Set limits
        set_time_limit(180); // 3 minutes
        ini_set('memory_limit', '1024M'); // 1GB

        $this->domains = &$domains;
        $this->neighbors = &$neighbors;
        $this->variables = &$variables;
        $this->assignments = [];
        $this->resetStatistics();

        Log::info("=== CSP Solver (Optimized) ===");
        Log::info("Variables: " . count($variables));
        Log::info("Initial domains: " . array_sum(array_map('count', $domains)));

        // OPTIMIZATION 1: Skip AC3 if problem is small
        if (count($variables) < 50) {
            Log::info("Skipping AC3 for small problem");
        } else {
            $startTime = microtime(true);
            if (!$this->ac3($this->domains, $this->neighbors)) {
                Log::error("AC3 failed - problem unsolvable");
                return null;
            }
            Log::info("AC3: " . round(microtime(true) - $startTime, 2) . "s");
        }

        // OPTIMIZATION 2: Backtracking with aggressive pruning
        $startTime = microtime(true);
        $result = $this->backtrack($this->domains, $this->neighbors);

        if ($result === null) {
            Log::error("No solution found");
            $this->logStatistics();
            return null;
        }

        Log::info("=== Solution Found ===");
        Log::info("Time: " . round(microtime(true) - $startTime, 2) . "s");
        $this->logStatistics();

        return $result;
    }

    /**
     * OPTIMIZED: AC3 with early termination
     */
    public function ac3(array &$domains, array &$neighbors): bool
    {
        $queue = new SplQueue();

        foreach ($neighbors as $xi => $neighborList) {
            foreach ($neighborList as $xj) {
                $queue->enqueue([$xi, $xj]);
            }
        }

        $iterations = 0;
        $maxIterations = 50000; // Safety limit

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
                    break; // OPTIMIZATION: Stop at first support
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

    /**
     * FULLY OPTIMIZED: Constraint checking with early exits
     */
    private function isConsistent(int $xi, int $xj, array $valueI, array $valueJ): bool
    {
        // $this->constraintChecks++;

        // OPTIMIZATION: Check limit every 10000 checks
        if (
            $this->constraintChecks % 10000 === 0 &&
            $this->constraintChecks > $this->maxConstraintChecks
        ) {
            throw new \RuntimeException("Too many constraint checks - over-constrained");
        }

        // OPTIMIZATION 1: Early exit if same variable
        if ($xi === $xj) {
            return true;
        }

        // OPTIMIZATION 2: Extract once
        $timeI = $valueI['time_slot_id'];
        $timeJ = $valueJ['time_slot_id'];

        // OPTIMIZATION 3: Different times = no conflict
        if ($timeI !== $timeJ) {
            return true;
        }

        // Same time - check room conflict
        $roomI = $valueI['room_id'];
        $roomJ = $valueJ['room_id'];

        if ($roomI === $roomJ) {
            // OPTIMIZATION 4: Inline slot overlap check
            $slotI = $valueI['slot'];
            $slotJ = $valueJ['slot'];

            if ($slotI === 'full' || $slotJ === 'full' || $slotI === $slotJ) {
                return false; // Room conflict
            }
        }

        // Check instructor conflict
        $instructorI = $this->variables[$xi]['instructor_id'] ?? null;
        $instructorJ = $this->variables[$xj]['instructor_id'] ?? null;

        if ($instructorI !== null && $instructorJ !== null && $instructorI === $instructorJ) {
            $slotI = $valueI['slot'];
            $slotJ = $valueJ['slot'];

            if ($slotI === 'full' || $slotJ === 'full' || $slotI === $slotJ) {
                return false; // Instructor conflict
            }
        }

        return true;
    }

    /**
     * OPTIMIZED: Backtracking with loop detection
     */
    public function backtrack(array $domains, array $neighbors, array $assignment = []): ?array
    {
        $this->backtrackCalls++;

        // CRITICAL: Safety checks
        if ($this->backtrackCalls >= $this->maxBacktrackCalls) {
            Log::error("Max backtrack calls ({$this->maxBacktrackCalls}) exceeded");
            return null;
        }

        // Progress logging (every 100 calls)
        if ($this->backtrackCalls % 100 === 0) {
            $progress = count($assignment) . "/" . count($domains);
            Log::info("Backtrack #{$this->backtrackCalls}: {$progress} assigned");
        }

        // Terminal condition
        if (count($assignment) === count($domains)) {
            $this->assignments = $assignment;
            return $assignment;
        }

        // OPTIMIZATION: Simple MRV (no degree heuristic for speed)
        $var = $this->selectUnassignedVariable($domains, $assignment);

        if ($var === null) {
            return null;
        }

        $orderedValues = $domains[$var];

        if (empty($orderedValues)) {
            $this->domainWipeouts++;

            // CRITICAL: Detect infinite loops
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

        // Reset on successful different variable
        if ($var !== $this->lastFailedVar) {
            $this->consecutiveFailures = 0;
        }

        // Try each value
        foreach ($orderedValues as $value) {
            if ($this->isConsistentWithAssignment($var, $value, $assignment)) {
                $assignment[$var] = $value;

                // OPTIMIZATION: Forward check with early termination
                $domainsCopy = $domains;
                if ($this->forwardCheck($var, $value, $domainsCopy, $neighbors, $assignment)) {
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

    /**
     * OPTIMIZED: Simple MRV (no tie-breaking for speed)
     */
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

    /**
     * OPTIMIZED: Forward check with early termination
     */
    private function forwardCheck(int $var, array $value, array &$domains, array $neighbors, array $assignment): bool
    {
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
                return false; // Early termination
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
    }

    private function logStatistics(): void
    {
        Log::info("=== Statistics ===");
        Log::info("Backtracks: {$this->backtrackCalls}");
        Log::info("Checks: {$this->constraintChecks}");
        Log::info("Wipeouts: {$this->domainWipeouts}");
    }

    public function getResults(): array
    {
        return $this->assignments;
    }

    public function register(): void {}
    public function boot(): void {}
    public function __construct() {}
}
