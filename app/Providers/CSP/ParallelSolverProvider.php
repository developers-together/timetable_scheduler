<?php

namespace App\Providers\CSP;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ParallelSolverProvider
{
    private int $numWorkers = 12; // Number of parallel processes
    private int $timeoutSeconds = 120;
    private string $cachePrefix = 'csp_solver_';
    private string $cacheStore = 'file'; // Use file cache to avoid DB packet size limits
    
    public function __construct(int $workers = 12)
    {
        $this->numWorkers = $workers;
    }
    
    /**
     * Solve using parallel PHP processes
     * Each process tries with a different random seed
     */
    public function parallelSolve(array $domains, array $neighbors, array $variables): ?array
    {
        $startTime = microtime(true);
        $jobId = uniqid('csp_', true);
        
        Log::info("=== Starting Parallel Solver with {$this->numWorkers} workers ===");
        Log::info("Job ID: {$jobId}");
        
        // Store problem data in cache for workers to access
        $problemKey = $this->cachePrefix . $jobId . '_problem';
        Cache::store($this->cacheStore)->put($problemKey, [
            'domains' => $domains,
            'neighbors' => $neighbors,
            'variables' => $variables,
        ], 300); // 5 minutes TTL
        
        // Clear any previous results
        for ($i = 0; $i < $this->numWorkers; $i++) {
            Cache::store($this->cacheStore)->forget($this->cachePrefix . $jobId . '_result_' . $i);
        }
        
        // Launch worker processes
        $pids = [];
        for ($i = 0; $i < $this->numWorkers; $i++) {
            $seed = rand(1, 1000000);
            $cmd = sprintf(
                'php %s/artisan csp:worker %s %d %d > /dev/null 2>&1 & echo $!',
                base_path(),
                escapeshellarg($jobId),
                $i,
                $seed
            );
            
            $pid = trim(shell_exec($cmd));
            $pids[$i] = $pid;
            Log::info("Launched worker {$i} with PID {$pid}, seed {$seed}");
        }
        
        // Wait for first result or timeout
        $result = null;
        $elapsed = 0;
        $checkInterval = 0.5; // Check every 500ms
        
        while ($elapsed < $this->timeoutSeconds) {
            // Check for results from any worker
            for ($i = 0; $i < $this->numWorkers; $i++) {
                $resultKey = $this->cachePrefix . $jobId . '_result_' . $i;
                $workerResult = Cache::store($this->cacheStore)->get($resultKey);
                
                if ($workerResult !== null && isset($workerResult['success']) && $workerResult['success']) {
                    $result = $workerResult['assignment'];
                    Log::info("Worker {$i} found solution in " . round(microtime(true) - $startTime, 2) . "s");
                    break 2;
                }
            }
            
            usleep((int)($checkInterval * 1000000));
            $elapsed = microtime(true) - $startTime;
        }
        
        // Kill remaining workers
        foreach ($pids as $i => $pid) {
            if (is_numeric($pid) && $pid > 0) {
                @shell_exec("kill -9 {$pid} 2>/dev/null");
            }
        }
        
        // Cleanup cache
        Cache::store($this->cacheStore)->forget($problemKey);
        for ($i = 0; $i < $this->numWorkers; $i++) {
            Cache::store($this->cacheStore)->forget($this->cachePrefix . $jobId . '_result_' . $i);
        }
        
        $totalTime = microtime(true) - $startTime;
        Log::info("Parallel solver completed in " . round($totalTime, 2) . "s");
        
        return $result;
    }
}
