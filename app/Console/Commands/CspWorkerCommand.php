<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Providers\CSP\ConstraintSolverProvider;

class CspWorkerCommand extends Command
{
    protected $signature = 'csp:worker {jobId} {workerId} {seed}';
    protected $description = 'CSP solver worker process';

    private string $cacheStore = 'file'; // Must match ParallelSolverProvider
    
    public function handle()
    {
        $jobId = $this->argument('jobId');
        $workerId = (int)$this->argument('workerId');
        $seed = (int)$this->argument('seed');
        
        // Set random seed for this worker
        mt_srand($seed);
        
        // Get problem from cache (using file store to match parallel solver)
        $problemKey = 'csp_solver_' . $jobId . '_problem';
        $problem = Cache::store($this->cacheStore)->get($problemKey);
        
        if (!$problem) {
            $this->error("Problem data not found in cache");
            return 1;
        }
        
        $domains = $problem['domains'];
        $neighbors = $problem['neighbors'];
        $variables = $problem['variables'];
        
        // Shuffle domains with this worker's seed
        foreach ($domains as $var => &$domain) {
            shuffle($domain);
        }
        unset($domain);
        
        // Create solver and try to solve
        $solver = new ConstraintSolverProvider();
        $result = $solver->solve($domains, $neighbors, $variables);
        
        // Store result in cache
        $resultKey = 'csp_solver_' . $jobId . '_result_' . $workerId;
        
        if ($result !== null) {
            Cache::store($this->cacheStore)->put($resultKey, [
                'success' => true,
                'assignment' => $result,
                'worker' => $workerId,
            ], 300);
            $this->info("Worker {$workerId} found solution!");
        } else {
            Cache::store($this->cacheStore)->put($resultKey, [
                'success' => false,
                'worker' => $workerId,
            ], 300);
            $this->warn("Worker {$workerId} failed to find solution");
        }
        
        return 0;
    }
}
