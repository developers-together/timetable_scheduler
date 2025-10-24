<?php

namespace App\Imports;

use App\Models\CourseComponent;
use Maatwebsite\Excel\Concerns\ToModel;

class CourseComponentImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $component = new CourseComponent([
            'code' => $row[0],
            'name' => $row[1],
        ]);



        return $component;
    }
}
