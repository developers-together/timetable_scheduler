<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{


    protected $fillable = ['code', 'name'];

    public function components(): HasMany
    {
        return $this->HasMany(CourseComponent::class);
    }

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(Instructor::class);
    }

    public function requiredCourse(): HasOne
    {
        return $this->HasOne(RequiredCourse::class);
    }
}
