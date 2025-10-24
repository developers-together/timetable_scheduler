<?php

namespace App\Providers\CSP;

use Illuminate\Support\ServiceProvider;
use SplQueue;


class ConstraintSolverProvider extends ServiceProvider
{

    public function __construct() {}

    private $assignments = [];


    public function getResults()
    {
        return $this->assignments;
    }
    public function ac3($domains, $neighbors)
    {

        $queue = new SplQueue();

        foreach ($neighbors as $xi => $nlist) {
            foreach ($nlist as $xj) {
                $queue->enqueue([$xi, $xj]);
            }

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
        }
    }

    private function isconsistent(array $a, array $b)
    {

        if (
            $a['room_id'] != $b['room_id'] && $a['time_slot_id'] === $b['time_slot_id']
            || $a['instructor_id'] === $b['instructor_id'] && $a['time_slot_id'] != $b['time_slot_id']
        ) {
            return false;
        }
        return true;
    }


    private function revise($xi, $xj, $domains)
    {
        $revised = false;
        $newDomain = [];

        foreach ($domains[$xi] as $i) {
            $hasSupport = false;
            foreach ($domains[$xj] as $j) {
                if ($this->isconsistent($i, $j)) {
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

    private function consistentWithAssignment(array $value, array $assignment): bool
    {
        foreach ($assignment as $val) {
            if (!$this->isConsistent($value, $val)) {
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
                if ($this->isconsistent($value, $neighborValue)) {
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
        if (count($assignment) === count($domains)) {
            $this->assignments = $assignment;
            return $assignment;
        }

        $var = $this->bestVariable($domains, $assignment, $neighbors);
        $orderedValues = $this->smallestDomain($var, $domains, $assignment, $neighbors);

        foreach ($orderedValues as $value) {
            if ($this->consistentWithAssignment($value, $assignment)) {
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
