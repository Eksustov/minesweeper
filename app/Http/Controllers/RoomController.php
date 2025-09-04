<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::all(); // Optional: filter only public rooms
        return view('rooms.index', compact('rooms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:public,private',
            'max_players' => 'required|integer|min:2|max:10'
        ]);

        $room = Room::create([
            'code' => strtoupper(Str::random(6)), // Generate a 6-character code
            'user_id' => Auth::id(),
            'type' => $request->type,
            'max_players' => $request->max_players
        ]);

        return redirect()->route('rooms.index')->with('success', 'Room created: '.$room->code);
    }
}
