<?php

namespace App\Imports;

use App\Models\Instructor;
use App\Models\InstructorRole;
use App\Models\Course;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstructorsImport implements OnEachRow
{

    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $row      = $row->toArray();

        $instructor = Instructor::firstOrCreate([
            'name' => $row[1],

        ]);

        $instructor->roles()->create([
            'role' => $row[0],
        ]);

        $courses = str::of($row[2])->explode(',')->toArray();

        foreach ($courses as $courseCode) {
            $course = Course::where('code', $courseCode)->first();

            if ($course) {
                $instructor->courses()->syncWithoutDetaching([$course->id]);
            } else {
                Log::warning("Skipped missing course: {$courseCode}");
                continue;
            }
        }
    }
}
