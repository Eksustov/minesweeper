<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Game;
use Illuminate\Http\Request;
use App\Events\{GameStarted, GameUpdated, TileUpdated};
use App\Services\MinesweeperService;

class GameController extends Controller
{

    protected $minesweeperService;

    public function __construct(MinesweeperService $minesweeperService)
    {
        $this->minesweeperService = $minesweeperService;
    }

    public function game(Room $room)
    {
        $game = $room->games()->where('started', true)->latest()->first();

        if (!$game) {
            [$rows,$cols,$mines] = [9,9,10];

            $board = $this->minesweeperService->generateBoard($rows,$cols,$mines);

            $game = $room->games()->create([
                'difficulty' => 'easy',
                'rows' => $rows,
                'cols' => $cols,
                'mines' => $mines,
                'board' => $board,        // <— array
                'flags' => [],
                'revealed' => [],
                'started' => true,
            ]);
        }

        return view('minesweeper', [
            'room' => $room,
            'game' => $game,
            'rows' => $game->rows,
            'cols' => $game->cols,
            'mines' => $game->mines,
            'board' => $game->board,          // <— already array via cast
            'flags' => $game->flags ?? [],
            'revealed' => $game->revealed ?? [],
        ]);
    }

    public function show(Room $room)
    {
        $game = $room->games()->where('started', true)->latest()->firstOrFail();
    
        return view('minesweeper', [
            'room' => $room,
            'game' => $game,
            'rows' => $game->rows,
            'cols' => $game->cols,
            'mines' => $game->mines,
            'board' => $game->board,           // <— array
            'flags' => $game->flags ?? [],
            'revealed' => $game->revealed ?? [],
        ]);
    }

    public function start(Room $room, Request $request)
    {
        if ($room->user_id !== auth()->id()) {
            return back()->with('error', 'Only the creator can start the game.');
        }
    
        if ($room->games()->where('started', true)->exists()) {
            return redirect()->route('games.show', $room);
        }
    
        [$rows,$cols,$mines] = $this->getGameSettings($request->input('difficulty','easy'), $request);
        $board = $this->minesweeperService->generateBoard($rows,$cols,$mines);
    
        $game = $room->games()->create([
            'difficulty' => $request->input('difficulty', 'easy'),
            'rows' => $rows,
            'cols' => $cols,
            'mines' => $mines,
            'board' => $board,           // <—
            'flags' => [],
            'revealed' => [],
            'started' => true,
        ]);
    
        broadcast(new GameStarted($room->id, $board, $rows, $cols, $mines))->toOthers();
    
        return redirect()->route('games.show', $room);
    }

    public function update(Request $request, Room $room)
    {
        $request->validate([
            'row'    => 'nullable|integer',
            'col'    => 'nullable|integer',
            'action' => 'required|in:reveal,flag',
            'value'  => 'nullable', // used for flag true/false
        ]);

        $game  = $room->games()->where('started', true)->latest()->firstOrFail();

        // ensure arrays via casts
        $board = $game->board ?? [];
        $flags = $game->flags ?? [];
        $revealed = $game->revealed ?? [];

        $rows  = (int) $game->rows;
        $cols  = (int) $game->cols;
        $mines = (int) $game->mines;

        $row    = (int) $request->input('row');
        $col    = (int) $request->input('col');
        $action = $request->string('action');
        $key    = "{$row}-{$col}";

        // get player's color in this room (for flag background)
        $playerColor = optional(
            $request->user()->rooms()->where('rooms.id', $room->id)->first()
        )->pivot->color ?? '#000000';

        if ($action === 'flag') {
            // toggle according to value
            if ($request->boolean('value')) {
                $flags[$key] = $playerColor;
                unset($revealed[$key]);
                if (isset($board[$row][$col]['revealed'])) {
                    $board[$row][$col]['revealed'] = false;
                }
            } else {
                unset($flags[$key]);
            }

            $game->flags = $flags;
            $game->revealed = $revealed;
            $game->board = $board;
            $game->save();

            broadcast(new \App\Events\TileUpdated(
                $room->id,
                $row,
                $col,
                'flag',
                $request->boolean('value'), // value
                false,                      // gameOver
                $playerColor                // playerColor
            ))->toOthers();

            return response()->json(['status' => 'ok', 'flags' => $flags]);
        }

        // === REVEAL path ===

        if (!isset($board[$row][$col])) {
            return response()->json(['status' => 'error', 'message' => 'Invalid tile'], 422);
        }

        // 1) Do not reveal a flagged tile
        if (isset($flags[$key])) {
            return response()->json(['status' => 'ok', 'cells' => [], 'gameOver' => false]);
        }

        // 2) Collect reveal while respecting flags
        $result = $this->minesweeperService->collectReveal($board, $rows, $cols, $row, $col, $flags);
        $cells  = $result['cells'];

        if (empty($cells)) {
            return response()->json(['status' => 'ok', 'cells' => [], 'gameOver' => false]);
        }

        // 3) Double guard (in case): strip any flagged cells from set
        $cells = array_values(array_filter($cells, function ($c) use ($flags) {
            return !isset($flags["{$c['row']}-{$c['col']}"]);
        }));

        if (empty($cells)) {
            return response()->json(['status' => 'ok', 'cells' => [], 'gameOver' => false]);
        }

        // 4) Persist revealed to board + revealed map
        foreach ($cells as $c) {
            $revealed["{$c['row']}-{$c['col']}"] = true;
            $board[$c['row']][$c['col']]['revealed'] = true;
        }

        // 5) Win/lose check
        $safeTotal = $rows * $cols - $mines;
        $safeRevealed = 0;
        for ($r = 0; $r < $rows; $r++) {
            for ($cc = 0; $cc < $cols; $cc++) {
                if (empty($board[$r][$cc]['mine']) && !empty($board[$r][$cc]['revealed'])) {
                    $safeRevealed++;
                }
            }
        }
        $gameOver = $result['hitMine'] || ($safeRevealed >= $safeTotal);

        // 6) Save
        $game->board    = $board;
        $game->revealed = $revealed;
        $game->save();

        // 7) Broadcast authoritative reveal set
        broadcast(new \App\Events\TileUpdated(
            $room->id,
            $row,
            $col,
            'reveal',
            $cells,     // array of {row,col,mine,count}
            $gameOver,
            null
        ))->toOthers();

        return response()->json(['status' => 'ok', 'cells' => $cells, 'gameOver' => $gameOver]);
    }

    public function restart(Room $room)
    {
        if ($room->user_id !== auth()->id()) {
            return response()->json(['status'=>'error','message'=>'Only the creator can restart.'], 403);
        }
    
        $game = $room->games()->latest()->first();
        if (!$game) {
            return response()->json(['status'=>'error','message'=>'No game found.'], 404);
        }
    
        $board = $this->minesweeperService->generateBoard($game->rows, $game->cols, $game->mines);
    
        $game->update([
            'board' => $board,       // <—
            'flags' => [],
            'revealed' => [],
            'started' => true,
        ]);
    
        broadcast(new GameStarted($room->id, $board, $game->rows, $game->cols, $game->mines))->toOthers();
    
        return response()->json([
            'status' => 'ok',
            'board'  => $board,
            'rows'   => $game->rows,
            'cols'   => $game->cols,
            'mines'  => $game->mines,
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
