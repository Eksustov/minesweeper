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
            'value'  => 'nullable', // for flag
        ]);

        $game = $room->games()->where('started', true)->latest()->firstOrFail();

        $board = $game->board ?? [];
        $rows  = (int)$game->rows;
        $cols  = (int)$game->cols;
        $mines = (int)$game->mines;

        $action = $request->string('action');
        $row    = $request->input('row');
        $col    = $request->input('col');

        // player color for flags
        $playerColor = optional($request->user()->rooms()->where('rooms.id',$room->id)->first())->pivot->color;

        if ($action === 'flag') {
            if (!isset($board[$row][$col])) {
                return response()->json(['status'=>'error','message'=>'Invalid tile'], 422);
            }

            $flags = $game->flags ?? [];
            $key = "{$row}-{$col}";

            if ($request->boolean('value')) {
                $flags[$key] = $playerColor ?: '#000000';
            } else {
                unset($flags[$key]);
            }

            $game->flags = $flags;
            $game->save();

            broadcast(new \App\Events\TileUpdated(
                $room->id, $row, $col, 'flag', $request->boolean('value'), false, $playerColor
            ))->toOthers();

            return response()->json(['status'=>'ok','flags'=>$flags]);
        }

        // REVEAL
        if (!isset($board[$row][$col])) {
            return response()->json(['status'=>'error','message'=>'Invalid tile'], 422);
        }

        $result = $this->minesweeperService->collectReveal($board,$rows,$cols,(int)$row,(int)$col);
        $cells  = $result['cells'];
        if (empty($cells)) {
            return response()->json(['status'=>'ok']);
        }

        // persist revealed keys
        $revealed = $game->revealed ?? [];
        foreach ($cells as $c) {
            $revealed["{$c['row']}-{$c['col']}"] = true;
            // (optional) also mark on board for clarity
            $board[$c['row']][$c['col']]['revealed'] = true;
        }

        // Check win (all non-mines revealed)
        $safeTotal = $rows*$cols - $mines;
        $safeRevealed = 0;
        for ($r=0;$r<$rows;$r++) {
            for ($c=0;$c<$cols;$c++) {
                if (empty($board[$r][$c]['mine']) && !empty($board[$r][$c]['revealed'])) {
                    $safeRevealed++;
                }
            }
        }
        $gameOver = $result['hitMine'] || ($safeRevealed >= $safeTotal);

        $game->board = $board;
        $game->revealed = $revealed;
        $game->save();

        broadcast(new \App\Events\TileUpdated(
            $room->id,
            $row,
            $col,
            'reveal',
            $cells,           // <— array of revealed cells
            $gameOver,
            null              // playerColor not used for reveal
        ))->toOthers();

        return response()->json(['status'=>'ok','cells'=>$cells,'gameOver'=>$gameOver]);
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
