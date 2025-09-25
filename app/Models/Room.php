<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    // Relationship: A room can have many games (history)
    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function players()
    {
        return $this->belongsToMany(User::class, 'room_user')
            ->withPivot('color')
            ->withTimestamps();
    }
}
