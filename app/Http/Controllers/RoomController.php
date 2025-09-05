<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Events\PlayerJoined;

class RoomController extends Controller
{
    public function index()
    {
        // Only show rooms that are not empty
        $rooms = Room::with('players')
            ->whereHas('players')
            ->latest()
            ->get();

        return view('welcome', compact('rooms'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Prevent multiple rooms per creator
        $existingRoom = Room::where('user_id', $user->id)->first();
        if ($existingRoom) {
            return redirect()->route('rooms.show', $existingRoom)
                ->with('info', 'You already have a room.');
        }

        $request->validate([
            'type' => 'required|in:public,private',
            'max_players' => 'required|integer|min:2|max:10',
        ]);

        $room = Room::create([
            'code' => strtoupper(Str::random(6)),
            'user_id' => $user->id,
            'type' => $request->type,
            'max_players' => $request->max_players,
        ]);

        // Attach creator as first player
        $room->players()->attach($user->id);

        return redirect()->route('rooms.show', $room)
            ->with('success', 'Room created successfully.');
    }

    public function show(Room $room)
    {
        $room->load('players', 'creator');
        return view('rooms.show', compact('room'));
    }

    public function join(Room $room)
    {
        $user = auth()->user();

        // Check if the user is already in the room
        if ($room->players->contains($user->id)) {
            return redirect()->route('rooms.show', $room);
        }

        // Check max players
        if ($room->players->count() >= $room->max_players) {
            return redirect()->route('welcome')->with('error', 'Room is full.');
        }

        // Attach user to room
        $room->players()->attach($user->id);

        // Broadcast the event for realtime updates
        $room->players()->attach($user->id);
        $room->load('players'); // refresh relation
        broadcast(new PlayerJoined($room))->toOthers();

        // Redirect to room page
        return redirect()->route('rooms.show', $room);
    }

    public function leave(Room $room)
    {
        $user = auth()->user();

        // Detach player from room
        $room->players()->detach($user->id);
        $room->load('players'); // refresh relation
        broadcast(new PlayerJoined($room))->toOthers();

        // If the room is empty after leaving, delete it
        if ($room->players()->count() === 0) {
            $room->delete();
        }

        return redirect()->route('welcome')->with('success', 'You left the room.');
    }


    public function start(Room $room)
    {
        if ($room->user_id !== Auth::id()) {
            return back()->with('error', 'Only the creator can start the game.');
        }

        return view('minesweeper', compact('room'));
    }
}
