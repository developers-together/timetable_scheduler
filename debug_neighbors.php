<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Providers\CSP\VariableManagerProvider;
use Illuminate\Support\Facades\Log;

$vm = new VariableManagerProvider();
$variables = $vm->getVariables();
$neighbors = $vm->getNeighbors();

// Find CSC111 Lecture Group 1 and ECE111 Lab Group 1 for CSIT Level 1
$csc111_lec_g1 = null;
$ece111_lab_g1 = null;

foreach ($variables as $idx => $var) {
    if ($var['course_name'] === 'Fundamentals of Programming' && 
        $var['type'] === 'Lecture' && 
        $var['groupNO'] === 1 &&
        $var['faculty'] === 'CSIT' &&
        $var['year'] === 1) {
        $csc111_lec_g1 = $idx;
        echo "Found CSC111 Lecture Group 1: Variable $idx\n";
        print_r($var);
    }
    
    if ($var['course_name'] === 'Digital Logic Design' && 
        $var['type'] === 'Lab' && 
        $var['groupNO'] === 1 &&
        $var['faculty'] === 'CSIT' &&
        $var['year'] === 1) {
        $ece111_lab_g1 = $idx;
        echo "Found ECE111 Lab Group 1: Variable $idx\n";
        print_r($var);
    }
}

if ($csc111_lec_g1 !== null && $ece111_lab_g1 !== null) {
    echo "\nChecking if they are neighbors...\n";
    $areNeighbors = in_array($ece111_lab_g1, $neighbors[$csc111_lec_g1] ?? []);
    echo "Are they neighbors? " . ($areNeighbors ? "YES" : "NO") . "\n";
    
    if (!$areNeighbors) {
        echo "\n❌ BUG FOUND: These should be neighbors but they are not!\n";
        echo "This is why the conflict is allowed.\n";
    } else {
        echo "\n✅ They are neighbors, so the CSP solver should prevent the conflict.\n";
        echo "If conflict still occurs, the bug is in the isConsistent method.\n";
    }
}
