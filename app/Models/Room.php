<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'user_id',
        'type',
        'max_players',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function players()
    {
        return $this->belongsToMany(User::class, 'room_user');
    }
}
