<?php

namespace App\Providers\CSP;

use Illuminate\Support\ServiceProvider;
use App\Models\TimeSlot;

class EvaluatorProvider extends ServiceProvider
{
    public function evaluate($assignment)
    {
        $score = 0;

        // Load all timeslots once
        $timeSlots = TimeSlot::all()->keyBy('id');

        foreach ($assignment as $v => $val) {
            $slot = $timeSlots[$val['time_slot_id']] ?? null;

            if ($slot && $slot->time >= '09:00' && $slot->time <= '14:15') {
                $score += 10;
            } else {
                $score -= 10;
            }
        }

        return $score;
    }

    public function __construct() {}

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
