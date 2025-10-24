<?php

namespace App\Imports;

use App\Models\RequiredCourse;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\Course;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Illuminate\Support\Facades\Log;


class RequiredCoursesImport implements OnEachRow
{

    public function onRow(Row $row)
    {

        $rowIndex = $row->getIndex();
        $row      = $row->toArray();

        $courseCode = $row[0];

        $course = Course::where('code', $courseCode)->first();

        if (! $course) {
            Log::warning("Missing: '{$courseCode}' | DB sample: " . Course::pluck('code')->implode(', '));
        } else {

            $course->requiredCourse()->updateOrCreate(
                ['course_id' => $course->id],
                [
                    'required_capacity' => $row[1],
                    'level' => $row[2],
                    'faculty' => $row[3],
                    'term' => $row[4]
                ]
            );
        }

        // $required =  RequiredCourse::firstOrCreate([
        //     'course_id' => $course->id,
        //     'required_capacity' => $row[1],
        //     'level' => $row[2],
        //     'faculty' => $row[3],
        // ]);

        // return $required;

    }
}
