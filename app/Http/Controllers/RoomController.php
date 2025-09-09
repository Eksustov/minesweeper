<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Events\PlayerJoined;
use App\Events\RoomUpdated;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::with('players')->latest()->get();
        return view('welcome', compact('rooms'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Prevent multiple rooms per creator
        if ($existingRoom = Room::where('user_id', $user->id)->first()) {
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

        // Broadcast new room
        $room->load('players');
        broadcast(new RoomUpdated($room))->toOthers();

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

        if ($room->players->contains($user->id)) return redirect()->route('rooms.show', $room);

        if ($room->players->count() >= $room->max_players) return redirect()->route('welcome')->with('error', 'Room is full.');

        $room->players()->attach($user->id);

        // Broadcast both
        $room->load('players');
        broadcast(new RoomUpdated($room))->toOthers();     // update room list
        broadcast(new PlayerJoined($room))->toOthers();    // update player list

        return redirect()->route('rooms.show', $room);
    }

    public function leave(Room $room)
    {
        $user = auth()->user();
        $room->players()->detach($user->id);
        $room->load('players');

        if ($room->players()->count() === 0) {
            broadcast(new RoomUpdated($room))->toOthers();
            $room->delete();
        } else {
            broadcast(new RoomUpdated($room))->toOthers();
            broadcast(new PlayerJoined($room))->toOthers(); // <-- ensure player list updates
        }

        return redirect()->route('welcome')->with('success', 'You left the room.');
    }

    public function start(Request $request, Room $room)
    {
        if ($room->user_id !== auth()->id()) {
            return back()->with('error', 'Only the creator can start the game.');
        }

        $request->validate([
            'difficulty' => 'required|in:easy,medium,hard,custom',
            'rows' => 'nullable|integer|min:5|max:50',
            'cols' => 'nullable|integer|min:5|max:50',
            'mines' => 'nullable|integer|min:1',
        ]);

        $difficulty = $request->difficulty;

        if ($difficulty === 'easy') {
            $rows = 8; $cols = 8; $mines = 10;
        } elseif ($difficulty === 'medium') {
            $rows = 12; $cols = 12; $mines = 20;
        } elseif ($difficulty === 'hard') {
            $rows = 16; $cols = 16; $mines = 40;
        } else { // custom
            $rows = $request->rows;
            $cols = $request->cols;
            $mines = $request->mines;
        }

        return view('minesweeper', compact('room', 'rows', 'cols', 'mines'));
    }

    public function roomsJson()
    {
        $rooms = Room::with('players')->latest()->get()->map(function($room) {
            return [
                'id' => $room->id,
                'code' => $room->code,
                'type' => $room->type,
                'max_players' => $room->max_players,
                'current_players' => $room->players->count(),
            ];
        });

        return response()->json($rooms);
    }

}
