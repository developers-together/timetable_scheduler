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
        // $scheduler->generateSchedule();
        $result = $scheduler->generateSchedule();

        // dd($result); // prints timetable + score
        // return redirect('/timetable');

        return response()->json($result);
    }

    public function show()
    {
        $schedule = Schedule::with(relations: ['course', 'instructor', 'room', 'timeSlot'])->get();

        $data = [];

        foreach ($schedule as $item) {
            $courseCode = $item->course->code ?? 'UNKNOWN';
            $sessionType = strtolower($item->course_component_id ?? 'lecture');

            $data[] = [
                'course_code' => $courseCode,
                'type' => $sessionType,
                'slot' => $item->timeSlot->day . '-' . substr($item->timeSlot->start, 0, 5),
                'room_id' => $item->room->code ?? null,
                'instructor_id' => $item->instructor->name ?? null,
                'faculty' => $item->faculty ?? null,
                'year' => $item->level ?? null,
                'semester' => $item->term ?? null,
                'group' => $item->groupNO ?? null,
                'section' => $item->sectionNO ?? null,
                'course_name' => $item->course->name ?? null,
                'credit_hours' => $item->course->credit_hours ?? null,
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
    public function show1(Schedule $schedule) {
        $schedules = Schedule::with(['course', 'instructor', 'room', 'timeSlot'])->get();

        $structured = [];

        foreach ($schedules as $item) {
            $faculty = $item->faculty ?? 'Unknown Faculty';
            $level = $item->level ?? 'Unknown Level';
            $day = $item->timeSlot->day ?? 'Unknown Day';
            $slot = substr($item->timeSlot->start, 0, 5); // e.g., "09:00"

            // Build the hierarchy: Faculty -> Level -> Day -> Slot
            $structured[$faculty][$level][$day][$slot][] = [
                'course_code' => $item->course->code ?? null,
                'course_name' => $item->course->name ?? null,
                'type' => $item->course_component_id ?? null,
                'room' => $item->room->code ?? null,
                'instructor' => $item->instructor->name ?? null,
                'group' => $item->groupNO ?? null,
                'section' => $item->sectionNO ?? null,
                'slot' => $item->slot ?? 'full', // Add the slot type
            ];
        }

        return response()->json($structured);
    }

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
