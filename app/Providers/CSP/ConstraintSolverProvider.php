<?php

namespace App\Providers\CSP;

use Illuminate\Support\ServiceProvider;
use SplQueue;
use Illuminate\Support\Facades\Log;


class ConstraintSolverProvider extends ServiceProvider
{

    private $domains;
    private $neighbors;

    private $variables;

    public function __construct() {}

    public function solve(array &$domains, array &$neighbors, array &$variables)
    {
        $this->domains = &$domains;
        $this->neighbors = &$neighbors;
        $this->variables = &$variables;

        $this->ac3($this->domains, $this->neighbors);

        $this->backtrack($this->domains, $this->neighbors);

        return $this->assignments;
    }

    private $assignments = [];


    public function getResults()
    {
        return $this->assignments;
    }
    public function ac3(&$domains, &$neighbors)
    {
        $queue = new SplQueue();

        // Add all arcs to queue first
        foreach ($neighbors as $xi => $nlist) {
            foreach ($nlist as $xj) {
                $queue->enqueue([$xi, $xj]);
            }
        }

        // THEN process the entire queue
        while (!$queue->isEmpty()) {
            [$xi, $xj] = $queue->dequeue();
            if ($this->revise($xi, $xj, $domains)) {
                if (empty($domains[$xi])) {
                    return false;
                }
                foreach ($neighbors[$xi] as $xk) {
                    if ($xk != $xj) {
                        $queue->enqueue([$xk, $xi]);
                    }
                }
            }
        }

        return true;
    }

    private function isconsistent($xi, $xj, array $a, array $b)
    {
        $instructorA = $this->variables[$xi]['instructor_id'] ?? null;
        $instructorB = $this->variables[$xj]['instructor_id'] ?? null;

        // Room conflict
        if ($a['room_id'] == $b['room_id'] && $a['time_slot_id'] === $b['time_slot_id']) {
            return false;
        }

        // Instructor conflict
        if (
            !is_null($instructorA) && !is_null($instructorB) &&
            $instructorA === $instructorB && $a['time_slot_id'] == $b['time_slot_id']
        ) {
            return false;
        }

        return true;
    }


    private function revise($xi, $xj, &$domains)
    {
        $revised = false;
        $newDomain = [];

        foreach ($domains[$xi] as $i) {
            $hasSupport = false;
            foreach ($domains[$xj] as $j) {
                if ($this->isconsistent($xi, $xj, $i, $j)) {  // Pass variable indices
                    $hasSupport = true;
                    break;
                }
            }

            if ($hasSupport) {
                $newDomain[] = $i;
            } else {
                $revised = true;
            }
        }

        $domains[$xi] = $newDomain;
        return $revised;
    }

    private function bestVariable(array $domains, array $assignments, array $neighbors)
    {
        $minsize = PHP_INT_MAX;
        $best = null;

        foreach ($domains as $var => $domain) {
            if (isset($assignments[$var])) {
                continue;
            }
            if (count($domain) < $minsize) {
                $minsize = count($domain);
                $best = $var;
            }
        }

        return $best;
    }

    private function smallestDomain(string $var, array $domains, array $assignment, array $neighbors): array
    {
        return $domains[$var]; // basic version, could implement LCV later
    }

    private function consistentWithAssignment(string $var, array $value, array $assignment): bool
    {
        foreach ($assignment as $assignedVar => $assignedValue) {
            if (!$this->isconsistent($var, $assignedVar, $value, $assignedValue)) {
                return false;
            }
        }
        return true;
    }

    private function forwardCheck(string $var, array $value, array &$domains, array $neighbors, array $assignment): bool
    {
        foreach ($neighbors[$var] as $neighbor) {
            if (isset($assignment[$neighbor])) {
                continue;
            }

            $newDomain = [];
            foreach ($domains[$neighbor] as $neighborValue) {
                if ($this->isconsistent($var, $neighbor, $value, $neighborValue)) {
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

    public function backtrack(array $domains, array $neighbors, array $assignment = []): ?array
    {
        // DEBUG: Check if any domains are empty before starting
        foreach ($domains as $var => $domain) {
            if (empty($domain)) {
                Log::error("Variable {$var} has empty domain before backtracking starts!");
                return null;
            }
        }

        if (count($assignment) === count($domains)) {
            $this->assignments = $assignment;
            return $assignment;
        }

        $var = $this->bestVariable($domains, $assignment, $neighbors);
        $orderedValues = $this->smallestDomain($var, $domains, $assignment, $neighbors);

        foreach ($orderedValues as $value) {
            if ($this->consistentWithAssignment($var, $value, $assignment)) {
                $assignment[$var] = $value;

                $domainsCopy = $domains;
                if ($this->forwardCheck($var, $value, $domainsCopy, $neighbors, $assignment)) {
                    $result = $this->backtrack($domainsCopy, $neighbors, $assignment);
                    if ($result !== null) {
                        return $result;
                    }
                }

                unset($assignment[$var]);
            }
        }

        return null;
    }
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
