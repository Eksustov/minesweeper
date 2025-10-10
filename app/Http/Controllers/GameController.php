<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Game;
use Illuminate\Http\Request;
use App\Events\{GameStarted, GameUpdated, TileUpdated};
use App\Services\MinesweeperService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class GameController extends Controller
{

    protected $minesweeperService;

    public function __construct(MinesweeperService $minesweeperService)
    {
        $this->minesweeperService = $minesweeperService;
    }

    private function assertPlayerOrAbort(Room $room): void
    {
        $userId = auth()->id();
        if (!$userId || !$room->players->contains($userId)) {
            abort(403, 'You must be in this room to play.');
        }
    }

    private function rateLimitActions(Room $room, Request $request): ?\Illuminate\Http\JsonResponse
    {
        $userId = auth()->id();
        $row = (string) $request->input('row', '');
        $col = (string) $request->input('col', '');

        // 5 actions/user/second (reveal or flag)
        $globalKey = "game:rate:{$room->id}:{$userId}";
        $GLOBAL_MAX = 5;  // max attempts
        $GLOBAL_DECAY = 1; // seconds

        // 1 action per cell per second per user
        $cellKey = "game:cell:{$room->id}:{$userId}:{$row}-{$col}";
        $CELL_MAX = 1;
        $CELL_DECAY = 1; // seconds

        if (RateLimiter::tooManyAttempts($globalKey, $GLOBAL_MAX)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Too many actions. Slow down.',
                'retryAfter' => RateLimiter::availableIn($globalKey)
            ], 429);
        }
        if ($row !== '' && $col !== '' && RateLimiter::tooManyAttempts($cellKey, $CELL_MAX)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Too many actions on the same cell.',
                'retryAfter' => RateLimiter::availableIn($cellKey)
            ], 429);
        }

        RateLimiter::hit($globalKey, $GLOBAL_DECAY);
        if ($row !== '' && $col !== '') {
            RateLimiter::hit($cellKey, $CELL_DECAY);
        }

        return null;
    }

    public function game(Room $room)
    {
        $room->load('players');
        $this->assertPlayerOrAbort($room); 

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
        $room->load('players');
        $this->assertPlayerOrAbort($room); 

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
        $room->load('players');
        $this->assertPlayerOrAbort($room); 

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
        $room->load('players');
        $this->assertPlayerOrAbort($room); 

        if ($resp = $this->rateLimitActions($room, $request)) {
            return $resp; // 429s on spam
        }

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
        $action = $request->input('action');  
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
        // === REVEAL path ===
        if (!isset($board[$row][$col])) {
            return response()->json(['status' => 'error', 'message' => 'Invalid tile'], 422);
        }

        return DB::transaction(function () use ($room, $game, $row, $col) {
            // Lock and re-read latest state
            $locked = \App\Models\Game::query()->whereKey($game->id)->lockForUpdate()->first();

            $board    = is_array($locked->board)    ? $locked->board    : (is_string($locked->board)    ? json_decode($locked->board, true)    ?: [] : []);
            $flags    = is_array($locked->flags)    ? $locked->flags    : (is_string($locked->flags)    ? json_decode($locked->flags, true)    ?: [] : []);
            $revealed = is_array($locked->revealed) ? $locked->revealed : (is_string($locked->revealed) ? json_decode($locked->revealed, true) ?: [] : []);

            $rows = (int)$locked->rows;
            $cols = (int)$locked->cols;
            $mines = (int)$locked->mines;

            $key = "{$row}-{$col}";

            // 1) Hard stop: don't reveal flagged tile
            if (isset($flags[$key])) {
                return response()->json(['status' => 'ok', 'cells' => [], 'gameOver' => false]);
            }

            // 2) Collect reveal with *current* flags
            $result = app(\App\Services\MinesweeperService::class)
                ->collectReveal($board, $rows, $cols, $row, $col, $flags);

            $cells  = $result['cells'];
            if (empty($cells)) {
                return response()->json(['status' => 'ok', 'cells' => [], 'gameOver' => false]);
            }

            // 3) Strip any now-flagged cells (double guard)
            $cells = array_values(array_filter($cells, function ($c) use ($flags) {
                return !isset($flags["{$c['row']}-{$c['col']}"]);
            }));
            if (empty($cells)) {
                return response()->json(['status' => 'ok', 'cells' => [], 'gameOver' => false]);
            }

            // 4) Persist revealed — NEVER mark flagged
            foreach ($cells as $c) {
                $ckey = "{$c['row']}-{$c['col']}";
                if (isset($flags[$ckey])) continue;
                $revealed[$ckey] = true;
                $board[$c['row']][$c['col']]['revealed'] = true;
            }

            // 5) Win/lose check (clicked-only mine ends the game)
            $clickedWasMine = false;
            foreach ($cells as $c) {
                if ($c['row'] === $row && $c['col'] === $col && !empty($c['mine'])) {
                    $clickedWasMine = true;
                    break;
                }
            }

            // If the player clicked a mine, reveal ALL mines for everybody
            if ($clickedWasMine) {
                $mineCells = [];
                for ($rr = 0; $rr < $rows; $rr++) {
                    for ($cc = 0; $cc < $cols; $cc++) {
                        if (!empty($board[$rr][$cc]['mine'])) {
                            $mineCells[] = [
                                'row'   => $rr,
                                'col'   => $cc,
                                'mine'  => true,
                                'count' => (int)($board[$rr][$cc]['count'] ?? 0),
                            ];
                            $board[$rr][$cc]['revealed'] = true;
                            $revealed["{$rr}-{$cc}"] = true;
                        }
                    }
                }

                // Merge clicked region with all mines (dedupe by row/col)
                $byKey = [];
                foreach (array_merge($cells, $mineCells) as $item) {
                    $byKey["{$item['row']}-{$item['col']}"] = $item;
                }
                $cells = array_values($byKey);
            }

            // Win on all safe revealed
            $safeTotal = $rows * $cols - $mines;
            $safeRevealed = 0;
            for ($r = 0; $r < $rows; $r++) {
                for ($cc = 0; $cc < $cols; $cc++) {
                    if (empty($board[$r][$cc]['mine']) && !empty($board[$r][$cc]['revealed'])) {
                        $safeRevealed++;
                    }
                }
            }
            $gameOver = $clickedWasMine || ($safeRevealed >= $safeTotal);

            // 6) Save
            $game->board    = $board;
            $game->revealed = $revealed;
            $game->save();

            // 7) Broadcast authoritative reveal set to everyone
            broadcast(new \App\Events\TileUpdated(
                $room->id,
                $row,
                $col,
                'reveal',
                $cells,     // now contains all mines if mine was clicked
                $gameOver,
                null
            ))->toOthers();

            return response()->json(['status' => 'ok', 'cells' => $cells, 'gameOver' => $gameOver]);
        });
    }

    public function restart(Room $room)
    {
        $room->load('players');
        $this->assertPlayerOrAbort($room); 

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
