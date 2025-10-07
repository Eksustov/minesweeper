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
            // No game exists yet — generate and save
            [$rows, $cols, $mines] = [9, 9, 10]; // default easy mode
            $board = $this->minesweeperService->generateBoard($rows, $cols, $mines);

            $game = $room->games()->create([
                'difficulty' => 'easy',
                'rows' => $rows,
                'cols' => $cols,
                'mines' => $mines,
                'board' => json_encode($board),
                'started' => true,
            ]);
        } else {
            $board = $game->board; // already cast to array
            $rows = $game->rows;
            $cols = $game->cols;
            $mines = $game->mines;
        }

        return view('minesweeper', [
            'room' => $room,
            'game' => $game,
            'rows' => $rows,
            'cols' => $cols,
            'mines' => $mines,
            'board' => $board,
            'flags' => $game->flags ? json_decode($game->flags, true) : [],
            'revealed' => $game->revealed ? json_decode($game->revealed, true) : [],
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
            'board' => json_decode($game->board, true), // ✅ decode JSON into array
            'revealed' => $game->revealed ? json_decode($game->revealed, true) : [],
            'flags' => $game->flags ? json_decode($game->flags, true) : [],
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

        [$rows, $cols, $mines] = $this->getGameSettings($request->input('difficulty', 'easy'), $request);

        $board = app(MinesweeperService::class)->generateBoard($rows, $cols, $mines);

        $game = $room->games()->create([
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

    public function update(Request $request, Room $room)
    {
        $row = $request->input('row');
        $col = $request->input('col');
    
        \Log::info("Tile clicked:", ['row' => $row, 'col' => $col]);
    
        $game = $room->games()->where('started', true)->latest()->firstOrFail();
    
        // Decode board JSON if needed
        $board = is_array($game->board) ? $game->board : json_decode($game->board, true);
    
        if (!isset($board[$row][$col])) {
            \Log::error("Board dimensions:", ['rows' => count($board), 'cols' => count($board[0] ?? [])]);
            throw new \Exception("Invalid tile coordinates");
        }
    
        // Reveal tile
        $board[$row][$col]['revealed'] = true;
    
        // Save back
        $game->board = $board;
        $game->save();

        broadcast(new TileUpdated(
            $room->id,
            $row,
            $col,
            'reveal',
            $board[$row][$col],
            auth()->user()->name ?? 'unknown',
            false
        ))->toOthers();
        
    
        return response()->json(['board' => $board]);
    }    

    public function restart(Room $room)
    {
        if ($room->user_id !== auth()->id()) {
            return response()->json(['status' => 'error', 'message' => 'Only the creator can restart.'], 403);
        }

        $game = $room->games()->latest()->first();
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
