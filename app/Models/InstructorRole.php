<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorRole extends Model
{
    protected $fillable = ['instructor_id', 'role'];
    public $timestamps = false;
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }
}
