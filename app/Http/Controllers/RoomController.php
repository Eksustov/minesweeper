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
use App\Events\GameUpdated;
use App\Events\TileUpdated;
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

        // Predefined list of colors
        $colors = [
            '#FF5733', '#33FF57', '#3357FF', '#F1C40F',
            '#9B59B6', '#E67E22', '#1ABC9C', '#E74C3C',
        ];

        // Assign first color to creator
        $creatorColor = $colors[0];
        $room->players()->attach($user->id, ['color' => $creatorColor]);

        $room->load('players');
        broadcast(new RoomUpdated($room))->toOthers();

        return redirect()->route('rooms.show', $room)
            ->with('success', 'Room created successfully.');
    }

    public function show(Room $room)
    {
        $room->load('players', 'creator');

        // Get active game if exists
        $activeGame = $room->game()->where('started', true)->latest()->first();

        return view('rooms.show', [
            'room' => $room,
            'activeGame' => $activeGame,
        ]);
    }

    public function join(Room $room)
    {
        $user = auth()->user();

        // If already in the room, redirect to room
        if ($room->players->contains($user->id)) {
            return redirect()->route('rooms.show', $room);
        }

        // Allow joining even if the room is “full” for existing members
        if ($room->players->count() >= $room->max_players) {
            return redirect()->route('rooms.show', $room)
                ->with('error', 'Room is full.');
        }

        // Predefined list of colors
        $colors = [
            '#FF5733', // red
            '#33FF57', // green
            '#3357FF', // blue
            '#F1C40F', // yellow
            '#9B59B6', // purple
            '#E67E22', // orange
            '#1ABC9C', // teal
            '#E74C3C', // dark red
        ];

        // Get colors already taken in this room
        $takenColors = $room->players->pluck('pivot.color')->filter()->toArray();

        // Pick a color that’s not taken
        $availableColors = array_diff($colors, $takenColors);
        $assignedColor = count($availableColors) ? array_values($availableColors)[0] : '#000000'; // fallback black

        // Attach the user with the assigned color
        $room->players()->attach($user->id, ['color' => $assignedColor]);

        $room->load('players');
        broadcast(new RoomUpdated($room))->toOthers();
        broadcast(new PlayerJoined($room))->toOthers();

        return redirect()->route('rooms.show', $room);
    }

    public function start(Room $room, Request $request)
    {
        if ($room->user_id !== auth()->id()) {
            return back()->with('error', 'Only the creator can start the game.');
        }

        // Check if there's already an active game
        $existingGame = $room->game()->where('started', true)->latest()->first();
        if ($existingGame) {
            return redirect()->route('rooms.game', $room);
        }

        $difficulty = $request->input('difficulty', 'easy');
        $rows = 8; $cols = 8; $mines = 10;

        if ($difficulty === 'medium') {
            $rows = $cols = 12;
            $mines = 20;
        } elseif ($difficulty === 'hard') {
            $rows = $cols = 16;
            $mines = 40;
        } elseif ($difficulty === 'custom') {
            $rows = (int) $request->input('rows', 10);
            $cols = (int) $request->input('cols', 10);
            $mines = (int) $request->input('mines', 10);
        }

        // Create one shared game for the room
        $game = $room->game()->create([
            'difficulty' => $difficulty,
            'rows' => $rows,
            'cols' => $cols,
            'mines' => $mines,
            'board' => json_encode(app(MinesweeperService::class)->generateBoard($rows, $cols, $mines)),
            'started' => true,
        ]);

        broadcast(new GameStarted($room, $game->id))->toOthers();

        return redirect()->route('rooms.game', [$room]);
    }

    public function game(Room $room)
    {
        $game = $room->game()->where('started', true)->latest()->firstOrFail();

        return view('minesweeper', [
            'room' => $room,
            'game' => $game,
            'rows' => $game->rows,
            'cols' => $game->cols,
            'mines' => $game->mines,
            'board' => $game->board, 
            'revealed' => $game->revealed ? json_decode($game->revealed, true) : [],
            'flags' => $game->flags ? json_decode($game->flags, true) : [],
        ]);
    }

    public function leave(Room $room)
    {
        $user = auth()->user();
        $room->players()->detach($user->id);
        $room->load('players');

        if ($room->players->count() === 0) {
            broadcast(new RoomUpdated($room))->toOthers();
            $room->delete();
        } else {
            broadcast(new RoomUpdated($room))->toOthers();
            broadcast(new PlayerJoined($room))->toOthers();
        }

        return redirect()->route('welcome')->with('success', 'You left the room.');
    }

    public function roomsJson()
    {
        $userId = auth()->id();

        $rooms = Room::with('players')->get()->map(function ($room) use ($userId) {
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

    public function updateGame(Request $request)
    {
        broadcast(new GameUpdated(
            $request->roomId,
            $request->row,
            $request->col,
            $request->action,
            $request->value
        ))->toOthers();

        return response()->json(['status' => 'ok']);
    }

    
    public function update(Request $request)
    {
        $roomId = $request->input('roomId');
        $row = $request->input('row');
        $col = $request->input('col');
        $action = $request->input('action');
        $value = $request->input('value');
        $gameOver = (bool) $request->input('gameOver', false);

        // find room + active game
        $room = Room::findOrFail($roomId);
        $game = $room->game()
            ->where('started', true)
            ->latest()
            ->firstOrFail();

        // figure out player's color (if they are in the room)
        $player = $room->players()->where('user_id', auth()->id())->first();
        $playerColor = $player?->pivot->color ?? null;

        // FLAGS: store color (or remove) instead of storing boolean only
        if ($action === 'flag') {
            $flags = $game->flags ? json_decode($game->flags, true) : [];

            $key = "{$row}-{$col}";

            if ($value) {
                // place flag -> save player's color string so it can be restored
                $flags[$key] = $playerColor;
            } else {
                // remove flag
                if (isset($flags[$key])) {
                    unset($flags[$key]);
                }
            }

            $game->flags = json_encode($flags);
        }

        // REVEALS: value is expected to be an array of { row, col, mine, count } entries
        if ($action === 'reveal') {
            $revealed = $game->revealed ? json_decode($game->revealed, true) : [];

            foreach ($value as $cell) {
                $revealed[$cell['row'] . '-' . $cell['col']] = true;
            }

            $game->revealed = json_encode($revealed);
        }

        // If game over (someone hit a mine), mark started=false so the game is finished
        if ($gameOver) {
            $game->started = false;
        }

        $game->save();

        // Broadcast. For flags include playerColor; for reveal send value array.
        if ($action === 'flag') {
            broadcast(new TileUpdated($roomId, $row, $col, $action, $value, $gameOver, $playerColor))->toOthers();
        } else { // reveal
            broadcast(new TileUpdated($roomId, null, null, $action, $value, $gameOver, null))->toOthers();
        }

        return response()->json(['status' => 'ok']);
    }

    public function kick(Request $request, Room $room)
    {
        $user = auth()->user();
        $targetUserId = $request->input('user_id');

        // Only creator can kick
        if ($room->user_id !== $user->id) {
            return response()->json(['status' => 'error', 'message' => 'Only the room creator can kick players.'], 403);
        }

        // Prevent kicking yourself
        if ($targetUserId == $user->id) {
            return response()->json(['status' => 'error', 'message' => 'You cannot kick yourself.'], 400);
        }

        // Check if the player is in the room
        if (!$room->players->contains($targetUserId)) {
            return response()->json(['status' => 'error', 'message' => 'Player not found in this room.'], 404);
        }

        // Remove the player
        $room->players()->detach($targetUserId);

        // Broadcast to the room that a player was kicked
        broadcast(new \App\Events\PlayerKicked($room->id, $targetUserId))->toOthers();

        return response()->json(['status' => 'ok', 'message' => 'Player kicked successfully.']);
    }

}
