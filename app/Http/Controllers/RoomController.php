<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Events\PlayerJoined;
use App\Events\RoomUpdated;
use App\Events\GameStarted;
use App\Services\MinesweeperService;

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
        
            // Check if there is an active game
            $activeGame = $room->game()->latest()->first();
        
            return view('rooms.show', [
                'room' => $room,
                'activeGame' => $activeGame,
            ]);
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

    public function start(Room $room, Request $request)
    {
        if ($room->user_id !== auth()->id()) {
            return back()->with('error', 'Only the creator can start the game.');
        }

        // Check if a game already exists
        $existingGame = $room->game()->latest()->first();
        if ($existingGame) {
            return redirect()->route('rooms.game', $room);
        }

        // Determine settings based on difficulty
        $difficulty = $request->input('difficulty', 'easy');
        $rows = 8; $cols = 8; $mines = 10;

        if ($difficulty === 'medium') {
            $rows = $cols = 12; $mines = 20;
        } elseif ($difficulty === 'hard') {
            $rows = $cols = 16; $mines = 40;
        } elseif ($difficulty === 'custom') {
            $rows = (int) $request->input('rows', 10);
            $cols = (int) $request->input('cols', 10);
            $mines = (int) $request->input('mines', 10);
        }

        // Create the game only if it doesn’t exist
        $game = $room->game()->create([
            'difficulty' => $difficulty,
            'rows' => $rows,
            'cols' => $cols,
            'mines' => $mines,
            'board' => json_encode(app('App\Services\MinesweeperService')->generateBoard($rows, $cols, $mines)),
            'started' => true,
        ]);

        // Broadcast game started
        broadcast(new GameStarted($room, $game->id))->toOthers();

        return redirect()->route('rooms.game', $room);
    }

    public function game(Room $room)
    {
        $game = $room->game()->latest()->firstOrFail();
    
        return view('minesweeper', [
            'room' => $room,
            'game' => $game,
            'rows' => $game->rows,
            'cols' => $game->cols,
            'mines' => $game->mines,
            'board' => $game->board,
        ]);
    }

    public function roomsJson()
    {
        $userId = auth()->id();

        $rooms = Room::with('players')->latest()->get()->map(function($room) use ($userId) {
            return [
                'id' => $room->id,
                'code' => $room->code,
                'type' => $room->type,
                'max_players' => $room->max_players,
                'current_players' => $room->players->count(),
                'isInRoom' => $room->players->contains($userId),
            ];
        });

        return response()->json($rooms);
    }

}
