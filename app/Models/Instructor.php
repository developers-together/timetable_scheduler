<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Instructor extends Model
{
        protected $table = 'instructors';
    protected $primaryKey = 'instructor_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['instructor_id','instructor_name'];

    public function qualifiedCourses()
    {
        return $this->belongsToMany(Course::class, 'qualified_courses', 'instructor_id', 'course_id');
    }


    public function timetable(): BelongsTo

    {
        return $this->belongsTo(Timetable::class,'instructor_id','instructor_id');
    }
}
