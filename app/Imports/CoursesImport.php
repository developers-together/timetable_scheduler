<?php

namespace App\Imports;

use App\Models\Course;
use App\Models\CourseComponent;
// use Maatwebsite\Excel\Row;
// use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CoursesImport implements ToModel

{

    public function model(array $row)
    {

        // $rowIndex = $row->getIndex();
        // $row      = $row->toArray();
        try {
            $course = Course::firstOrCreate([
                'code' => $row[0],
                'name' => $row[1],
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to import course", [
                'row' => $row,
                'error' => $e->getMessage(),
            ]);
        }


        // $types = explode(',', string: $row[2]);
        //$array = Str::of($string)->explode(',')->toArray();

        $types = str::of($row[2])->explode(',')->toArray();

        // Log::debug("Importing course components for course ID {$course->id}: " . implode(', ', $types));

        foreach ($types as $componentData) {
            // Log::debug(message: $componentData);


            $component = new CourseComponent();
            $component->course_id = $course->id;
            $component->type = $componentData;
            $component->save();
        }



        // return $course;
    }
}
