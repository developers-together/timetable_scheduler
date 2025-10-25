<?php

namespace App\Http\Controllers;

use App\Models\RequiredCourse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use App\Imports\RequiredCoursesImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class GenerateInputController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('generate');
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'input' => 'required|mimes:xlsx'
        ]);

        $file = $request->file('input');

        Excel::import(new RequiredCoursesImport, $file);

        return Inertia::render('waiting');
    }

    /**
     * Display the specified resource.
     */
    public function show(RequiredCourse $requiredCourse)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RequiredCourse $requiredCourse)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RequiredCourse $requiredCourse)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RequiredCourse $requiredCourse)
    {
        //
    }
}
