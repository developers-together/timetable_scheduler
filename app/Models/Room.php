<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Room extends Model
{
    protected $table = 'rooms';
    protected $primaryKey = 'room_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['room_id','room_type','room_capacity'];

    public function timetable(): BelongsTo

    {
        return $this->belongsTo(Timetable::class,'room_id','room_id');
    }
}
