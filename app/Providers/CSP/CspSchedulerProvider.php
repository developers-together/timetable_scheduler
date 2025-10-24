<?php

namespace App\Providers\CSP;

use Illuminate\Support\ServiceProvider;
use SplQueue;
use App\Providers\CSP\VariableManagerProvider as VariableManager;
use App\Providers\CSP\ConstraintSolverProvider as ConstraintSolver;
use App\Providers\CSP\EvaluatorProvider as Evaluator;
use App\Providers\CSP\DatabaseSaverProvider as DatabaseSaver;


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

        $domains = $this->vars->getDomains();

        $neighbors = $this->vars->getNeighbors();
        $this->dbSaver->resetDB();
        $this->solver->ac3($domains, $neighbors);

        $assignment = $this->solver->backtrack($domains, $neighbors);

        if (!$assignment) {
            throw new \Exception("No valid timetable found!");
        }

        $score = $this->evaluator->evaluate($assignment);

        $this->dbSaver->saveOnDb($assignment, $score);

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
