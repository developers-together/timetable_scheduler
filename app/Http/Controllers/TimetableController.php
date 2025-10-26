<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;
use App\Providers\CSP\CspSchedulerProvider as CspScheduler;
use Inertia\Inertia;
use Inertia\Response;

class TimetableController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        $scheduler = new CspScheduler();
        $result = $scheduler->generateSchedule();

        dd($result); // prints timetable + score
    }

    public function show()
    {
        $schedule = Schedule::with(relations: ['course', 'instructor', 'room', 'timeSlot'])->get();

        $data = [];

        foreach ($schedule as $item) {
            $courseCode = $item->courses->code ?? 'UNKNOWN';
            $sessionType = strtolower($item->course_component_id ?? 'lecture');

            $data[$courseCode][$sessionType] = [
                'slot' => [$item->timeSlot->day, $item->timeSlot->start, $item->timeSlot->end],
                'room_id' => $item->room->code ?? null,
                'instructor_id' => $item->instructor->name ?? null,
                'faculty' => $item->faculty ?? null,
                'year' => $item->level ?? null,
                'semester' => $item->term ?? null,
                'group' => $item->group ?? null,
                'section' => $item->section ?? null,
                'course_name' => $item->course->name ?? null,
            ];
        }

        $response = [
            'success' => true,
            'message' => 'OK',
            'assignments' => count($schedule),
            'data' => $data
        ];

        return Inertia::render('timetable', [
            'timetable' => $response
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // $scheduler = new CspScheduler();
        // $result = $scheduler->generateSchedule();

        // dd($result);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show1(Schedule $schedule) {}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Schedule $schedule)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Schedule $schedule)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Schedule $schedule)
    {
        //
    }
}
