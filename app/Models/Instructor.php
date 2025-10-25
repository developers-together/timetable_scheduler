<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
// use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Instructor extends Model
{


    protected $fillable = ['name'];

    public function roles(): HasMany
    {
        return $this->HasMany(InstructorRole::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class);
    }

    public function requiredCourse(): HasOne
    {
        return $this->HasOne(RequiredCourse::class);
    }
}
