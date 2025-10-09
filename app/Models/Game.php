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
        'flags',
        'revealed',
        'started',
    ];

    protected $casts = [
        'board'    => 'array',   // <— store as array
        'flags'    => 'array',
        'revealed' => 'array',
        'started'  => 'boolean',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
