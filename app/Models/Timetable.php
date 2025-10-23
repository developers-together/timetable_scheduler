<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Timetable extends Model
{
    protected $table = 'timetable';
    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = ['day', 'start_time', 'end_time', 'level', 'room_id', 'course_id', 'instructor_id'];

    public function instructor(): HasOne

    {
        return $this->hasOne(Instructor::class, 'instructor_id', 'instructor_id');
    }

    public function room(): HasOne

    {
        return $this->hasOne(Room::class, 'room_id', 'room_id');
    }

    public function course(): HasOne

    {
        return $this->hasOne(Course::class, 'course_id', 'course_id');
    }
}
