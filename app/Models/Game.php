<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = [
        'room_id',
        'difficulty',
        'rows',
        'cols',
        'mines',
        'board',
        'started',
    ];

    protected $casts = [
        'board' => 'array',  // automatically converts JSON <-> array
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
