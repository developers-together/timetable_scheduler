<?php

namespace App\Providers\CSP;

use Illuminate\Support\ServiceProvider;
use SplQueue;
use App\Providers\CSP\VariableManagerProvider as VariableManager;
use App\Providers\CSP\ConstraintSolverProvider as ConstraintSolver;
use App\Providers\CSP\EvaluatorProvider as Evaluator;
use App\Providers\CSP\DatabaseSaverProvider as DatabaseSaver;
use Illuminate\Support\Facades\Log;

class CspSchedulerProvider extends ServiceProvider
{
    private VariableManager $vars;
    private ConstraintSolver $solver;
    private Evaluator $evaluator;
    private DatabaseSaver $dbSaver;

    public function __construct()
    {
        $this->vars = new VariableManager();
        $this->solver = new ConstraintSolver();
        $this->evaluator = new Evaluator();
        $this->dbSaver = new DatabaseSaver();
    }

    public function generateSchedule()
    {
        $variables = $this->vars->getVariables();

        if (!$variables) {
            throw new \Exception("No valid variables found!");
        }

        $domains = $this->vars->getDomains();

        if (!$domains) {
            throw new \Exception("No valid domains found!");
        }

        $neighbors = $this->vars->getNeighbors();

        if (!$neighbors) {
            throw new \Exception("No valid neighbors found!");
        }

        $this->dbSaver->resetDB();
        // $this->solver->ac3($domains, $neighbors);


        // $assignment = $this->solver->backtrack($domains, $neighbors);

        $assignment = $this->solver->solve($domains, $neighbors, $variables);



        if (!$assignment) {
            throw new \Exception("No valid timetable found!");
        }

        foreach ($assignment as $key => $value) {

            Log::info("Final Assignment: ");
            Log::info($variables[$key]);
            Log::info($key . " => " . json_encode($value));
        }

        $score = $this->evaluator->evaluate($assignment);

        Log::info("Final Score: " . $score);

        $this->dbSaver->saveOnDb($assignment, $score, $variables);

        return [
            'assignment' => $assignment,
            'score' => $score,
        ];
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
