<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Schedule extends Model
{
    protected $fillable = ['level', 'term', 'faculty', 'slot', 'course_id', 'course_component_id', 'instructor_id', 'room_id', 'time_slot_id', 'groupNO', 'sectionNO'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
    // public function courseComponents(): BelongsTo
    // {
    //     return $this->belongsTo(CourseComponent::class);
    // }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }
}
