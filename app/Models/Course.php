<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Course extends Model
{
    protected $table = 'courses';
    protected $primaryKey = 'course_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['course_id','course_name','credits','course_type'];

    public function qualifiedInstructors()
    {
        return $this->belongsToMany(Instructor::class, 'qualified_courses', 'course_id', 'instructor_id');
    }

    public function timetable(): BelongsTo

    {
        return $this->belongsTo(Timetable::class,'course_id','course_id');
    }
}
