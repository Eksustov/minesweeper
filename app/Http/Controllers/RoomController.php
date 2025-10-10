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
        $rooms = Room::with('players')
            ->where('type', 'public') // ⬅️ only public in index too (if used)
            ->latest()
            ->get();
        return view('welcome', compact('rooms'));
    }

    public function json(Request $request)
    {
        $perPage = max(1, min((int) $request->input('per_page', 10), 50));
        $rooms = \App\Models\Room::with('players')
            ->where('type', 'public')            // hide private rooms
            ->latest()
            ->paginate($perPage);                // <-- paginate
    
        // Map items for a slim payload
        $data = collect($rooms->items())->map(function ($room) {
            return [
                'id'               => $room->id,
                'code'             => $room->code,
                'type'             => $room->type,
                'max_players'      => $room->max_players,
                'current_players'  => $room->players->count(),
                'isInRoom'         => $room->players->contains(auth()->id()),
            ];
        })->values();
    
        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $rooms->currentPage(),
                'last_page'    => $rooms->lastPage(),
                'per_page'     => $rooms->perPage(),
                'total'        => $rooms->total(),
            ],
        ]);
    }

    public function joinByCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20', // your codes are 6 chars; keep flexible
        ]);

        $code = strtoupper(trim($request->input('code')));
        $room = Room::with('players', 'creator')->whereRaw('UPPER(code) = ?', [$code])->first();

        if (!$room) {
            return back()->withErrors(['code' => 'Room not found.'])->withInput();
        }

        // already in room?
        if ($room->players->contains(auth()->id())) {
            return redirect()->route('rooms.show', $room)
                ->with('info', 'You are already in this room.');
        }

        // capacity
        if ($room->players->count() >= $room->max_players) {
            return back()->withErrors(['code' => 'Room is full.'])->withInput();
        }

        // attach with a color
        $assignedColor = $this->assignColor($room);
        $room->players()->attach(auth()->id(), ['color' => $assignedColor]);

        $room->load('players');
        broadcast(new RoomUpdated($room))->toOthers();
        broadcast(new PlayerJoined($room))->toOthers();

        return redirect()->route('rooms.show', $room)->with('success', 'Joined room '.$room->code.'.');
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
        $targetUserId = (int) $request->input('user_id');

        if ($room->user_id !== $user->id) {
            return response()->json(['status' => 'error', 'message' => 'Only the creator can kick players.'], 403);
        }
        if ($targetUserId === $user->id) {
            return response()->json(['status' => 'error', 'message' => 'You cannot kick yourself.'], 400);
        }
        if (!$room->players->contains($targetUserId)) {
            return response()->json(['status' => 'error', 'message' => 'Player not in room.'], 404);
        }

        // Remove the player
        $room->players()->detach($targetUserId);
        $room->load('players', 'creator');

        // Build a lean players payload for the frontend
        $playersPayload = $room->players->map(function ($p) use ($room) {
            return [
                'id'     => $p->id,
                'name'   => $p->name,
                'color'  => $p->pivot->color ?? '#ccc',
                'isHost' => $p->id === $room->creator->id,
            ];
        })->values();

        // IMPORTANT:
        // - Send a "RoomUpdated" to everyone (do NOT chain ->toOthers()) so the HOST also receives it.
        // - Send a "PlayerKicked" with the kicked user's id so that user can show a modal + redirect.
        broadcast(new \App\Events\RoomUpdated($room, $playersPayload));
        broadcast(new \App\Events\PlayerKicked($room->id, $targetUserId));

        // Also respond with the fresh players list so the HOST updates immediately
        return response()->json([
            'status'  => 'ok',
            'message' => 'Player kicked.',
            'players' => $playersPayload,
        ]);
    }

    private function assignColor(Room $room): string
    {
        $colors = config('colors.list');
        $taken = $room->players->pluck('pivot.color')->filter()->toArray();
        $available = array_values(array_diff($colors, $taken));
        return $available[0] ?? '#000000';
    }
}

