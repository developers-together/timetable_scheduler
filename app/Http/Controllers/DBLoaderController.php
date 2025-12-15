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
        // Disable foreign key checks to allow truncating tables with relationships
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Truncate all tables that will be imported (in reverse dependency order)
        DB::table('course_instructor')->truncate();
        DB::table('course_components')->truncate();
        DB::table('instructor_roles')->truncate();
        DB::table('instructors')->truncate();
        DB::table('courses')->truncate();
        DB::table('rooms')->truncate();
        DB::table('time_slots')->truncate();
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        // Import fresh data
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
