<?php

$json = file_get_contents('timetable_dump.json');
$data = json_decode($json, true);

$conflictCount = 0;

foreach ($data as $faculty => $levels) {
    foreach ($levels as $level => $days) {
        foreach ($days as $day => $slots) {
            foreach ($slots as $time => $classes) {
                // Group by group number, but track slot for each
                $groupSlots = []; // [group][slot] = class
                
                foreach ($classes as $class) {
                    $group = $class['group'];
                    $slot = $class['slot'] ?? 'full';
                    $type = $class['type'];
                    $course = $class['course_code'];
                    
                    // Check for actual conflicts considering slots
                    if (isset($groupSlots[$group])) {
                        foreach ($groupSlots[$group] as $existingSlot => $existing) {
                            // Slots conflict if: either is full OR slots are the same
                            if ($slot === 'full' || $existingSlot === 'full' || $slot === $existingSlot) {
                                echo "Conflict in $faculty - Level $level - $day - $time (slots: $existingSlot vs $slot):\n";
                                echo "  1. {$existing['course_code']} ({$existing['type']}) Group $group\n";
                                echo "  2. $course ($type) Group $group\n";
                                $conflictCount++;
                            }
                        }
                    }
                    
                    if (!isset($groupSlots[$group])) {
                        $groupSlots[$group] = [];
                    }
                    $groupSlots[$group][$slot] = $class;
                }
            }
        }
    }
}

echo "\nTotal conflicts found: $conflictCount\n";
