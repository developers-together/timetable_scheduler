<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Schedule extends Model
{
    protected $fillable = ['level', 'term', 'faculty', 'slot', 'course_id', 'course_component_id', 'instructor_id', 'room_id', 'time_slot_id', 'groupNO', 'sectionNO'];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class);
    }

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(Instructor::class);
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class);
    }
    public function courseComponents(): BelongsToMany
    {
        return $this->belongsToMany(CourseComponent::class);
    }

    public function timeSlots(): BelongsToMany
    {
        return $this->belongsToMany(TimeSlot::class);
    }
}
