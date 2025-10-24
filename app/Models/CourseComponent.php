<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;



class CourseComponent extends Model
{
    protected $fillable = ['course_id', 'type'];
    public $timestamps = false;
    public function course(): BelongsToMany
    {
        return $this->BelongsToMany(Course::class);
    }
}
