<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Events\{PlayerJoined, PlayerLeft, PlayerKicked, RoomUpdated};

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::with('players')->latest()->get();
        return view('welcome', compact('rooms'));
    }

    public function json()
    {
        $rooms = \App\Models\Room::with('players')
            ->get()
            ->map(function ($room) {
                return [
                    'id' => $room->id,
                    'code' => $room->code,
                    'type' => $room->type,
                    'max_players' => $room->max_players,
                    'current_players' => $room->players->count(),
                    'isInRoom' => $room->players->contains(auth()->id()),
                ];
            });

        return response()->json($rooms);
    }

    private function refreshRoom(Room $room): void
    {
        $room->load('players');
        broadcast(new RoomUpdated($room))->toOthers();
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ($existingRoom = Room::where('user_id', $user->id)->first()) {
            return redirect()->route('rooms.show', $existingRoom)
                ->with('info', 'You already have a room.');
        }

        $data = $request->validate([
            'type' => 'required|in:public,private',
            'max_players' => 'required|integer|min:2|max:10',
        ]);

        $room = Room::create([
            'code' => strtoupper(Str::random(6)),
            'user_id' => $user->id,
            'type' => $data['type'],
            'max_players' => $data['max_players'],
        ]);

        $room->players()->attach($user->id, ['color' => config('colors.list')[0]]);

        $room->load('players');
        broadcast(new RoomUpdated($room))->toOthers();

        return redirect()->route('rooms.show', $room)
            ->with('success', 'Room created successfully.');
    }

    public function show(Room $room)
    {
        $room->load('players', 'creator');
        return view('rooms.show', compact('room'))
            ->with('activeGame', $room->games()->where('started', true)->latest()->first());
    }

    public function join(Room $room)
    {
        $user = auth()->user();

        if ($room->players->contains($user->id)) {
            return redirect()->route('rooms.show', $room);
        }

        if ($room->players->count() >= $room->max_players) {
            return redirect()->route('rooms.show', $room)->with('error', 'Room is full.');
        }

        $assignedColor = $this->assignColor($room);
        $room->players()->attach($user->id, ['color' => $assignedColor]);

        $room->load('players');
        broadcast(new RoomUpdated($room))->toOthers();
        broadcast(new PlayerJoined($room))->toOthers();

        return redirect()->route('rooms.show', $room);
    }

    public function leave(Room $room)
    {
        $room->players()->detach(auth()->id());
        $room->load('players');

        if ($room->players->isEmpty()) {
            broadcast(new RoomUpdated($room))->toOthers();
            $room->delete();
        } else {
            broadcast(new RoomUpdated($room))->toOthers();
            broadcast(new PlayerLeft($room))->toOthers();
        }

        return redirect()->route('welcome')->with('success', 'You left the room.');
    }

    public function kick(Request $request, Room $room)
    {
        $user = auth()->user();
        $targetUserId = $request->input('user_id');

        if ($room->user_id !== $user->id) {
            return response()->json(['status' => 'error', 'message' => 'Only the creator can kick players.'], 403);
        }

        if ($targetUserId == $user->id) {
            return response()->json(['status' => 'error', 'message' => 'You cannot kick yourself.'], 400);
        }

        if (!$room->players->contains($targetUserId)) {
            return response()->json(['status' => 'error', 'message' => 'Player not in room.'], 404);
        }

        $room->players()->detach($targetUserId);
        $room->load('players');

        broadcast(new PlayerLeft($room))->toOthers();
        broadcast(new PlayerKicked($room->id, $targetUserId))->toOthers();

        return response()->json(['status' => 'ok', 'message' => 'Player kicked.']);
    }

    private function assignColor(Room $room): string
    {
        $colors = config('colors.list');
        $taken = $room->players->pluck('pivot.color')->filter()->toArray();
        $available = array_values(array_diff($colors, $taken));
        return $available[0] ?? '#000000';
    }
}

