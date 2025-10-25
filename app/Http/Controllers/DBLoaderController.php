<?php

namespace App\Http\Controllers;

use App\Imports\CoursesImport;
use App\Imports\InstructorsImport;
use App\Imports\RoomsImport;
use App\Imports\TimeSlotsImport;
use App\Imports\RequiredCoursesImport;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;


use App\Models\Course;
use App\Models\RequiredCourse;

class DBLoaderController extends Controller
{

    public function import()
    {
        Excel::import(new CoursesImport, 'courses.xlsx');
        Excel::import(new RoomsImport, filePath: 'rooms.xlsx');
        Excel::import(new InstructorsImport, filePath: 'instructors.xlsx');

        Excel::import(new TimeSlotsImport, filePath: 'slots.xlsx');
    }

    public function importInput()
    {

        DB::table('required_courses')->truncate();
        Excel::import(new RequiredCoursesImport, filePath: 'input.xlsx');
    }
}
