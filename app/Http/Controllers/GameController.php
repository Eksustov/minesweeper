<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use App\Events\{GameStarted, GameUpdated, TileUpdated};
use App\Services\MinesweeperService;

class GameController extends Controller
{
    public function show(Room $room)
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

    public function start(Room $room, Request $request)
    {
        if ($room->user_id !== auth()->id()) {
            return back()->with('error', 'Only the creator can start the game.');
        }

        if ($room->game()->where('started', true)->exists()) {
            return redirect()->route('games.show', $room);
        }

        [$rows, $cols, $mines] = $this->getGameSettings($request->input('difficulty', 'easy'), $request);

        $board = app(MinesweeperService::class)->generateBoard($rows, $cols, $mines);

        $game = $room->game()->create([
            'difficulty' => $request->input('difficulty', 'easy'),
            'rows' => $rows,
            'cols' => $cols,
            'mines' => $mines,
            'board' => json_encode($board),
            'started' => true,
        ]);

        broadcast(new GameStarted($room->id, $board, $rows, $cols, $mines))->toOthers();

        return redirect()->route('games.show', $room);
    }

    public function update(Request $request)
    {
        $roomId = $request->input('roomId');
        $row = $request->input('row');
        $col = $request->input('col');
        $action = $request->input('action');
        $value = $request->input('value');
        $gameOver = (bool) $request->input('gameOver', false);

        $room = Room::findOrFail($roomId);
        $game = $room->game()->where('started', true)->latest()->firstOrFail();

        $player = $room->players()->where('user_id', auth()->id())->first();
        $playerColor = $player?->pivot->color ?? null;

        if ($action === 'flag') {
            $flags = $game->flags ? json_decode($game->flags, true) : [];
            $key = "{$row}-{$col}";
            $value ? $flags[$key] = $playerColor : unset($flags[$key]);
            $game->flags = json_encode($flags);
        }

        if ($action === 'reveal') {
            $revealed = $game->revealed ? json_decode($game->revealed, true) : [];
            foreach ($value as $cell) {
                $revealed[$cell['row'] . '-' . $cell['col']] = true;
            }
            $game->revealed = json_encode($revealed);
        }

        if ($gameOver) {
            $game->started = false;
        }

        $game->save();

        $event = $action === 'flag'
            ? new TileUpdated($roomId, $row, $col, $action, $value, $gameOver, $playerColor)
            : new TileUpdated($roomId, null, null, $action, $value, $gameOver, null);

        broadcast($event)->toOthers();

        return response()->json(['status' => 'ok']);
    }

    public function restart(Room $room)
    {
        if ($room->user_id !== auth()->id()) {
            return response()->json(['status' => 'error', 'message' => 'Only the creator can restart.'], 403);
        }

        $game = $room->game()->latest()->first();
        if (!$game) {
            return response()->json(['status' => 'error', 'message' => 'No game found.'], 404);
        }

        $board = app(MinesweeperService::class)->generateBoard($game->rows, $game->cols, $game->mines);

        $game->update([
            'board' => json_encode($board),
            'flags' => json_encode([]),
            'revealed' => json_encode([]),
            'started' => true,
        ]);

        broadcast(new GameStarted($room->id, $board, $game->rows, $game->cols, $game->mines))->toOthers();

        return response()->json([
            'status' => 'ok',
            'board' => $board,
            'rows' => $game->rows,
            'cols' => $game->cols,
            'mines' => $game->mines,
        ]);
    }

    private function getGameSettings(string $difficulty, Request $request): array
    {
        return match ($difficulty) {
            'medium' => [12, 12, 20],
            'hard'   => [16, 16, 40],
            'custom' => [
                (int) $request->input('rows', 10),
                (int) $request->input('cols', 10),
                (int) $request->input('mines', 10),
            ],
            default  => [8, 8, 10], // easy
        };
    }
}
