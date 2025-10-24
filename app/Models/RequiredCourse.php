<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class RequiredCourse extends Model
{
    protected $fillable = ['course_id', 'required_capacity', 'level', 'faculty'];

    public function course(): BelongsTo
    {
        return $this->BelongsTo(Course::class);
    }
}
