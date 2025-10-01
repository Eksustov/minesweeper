<?php

namespace App\Services;
use App\Models\Game;

class MinesweeperService
{
    /**
     * Generate a new Minesweeper board
     *
     * @param int $rows
     * @param int $cols
     * @param int $mines
     * @return array
     */
    public function generateBoard(int $rows, int $cols, int $mines): array
    {
        // Initialize board with empty cells
        $board = [];
        for ($r = 0; $r < $rows; $r++) {
            $board[$r] = [];
            for ($c = 0; $c < $cols; $c++) {
                $board[$r][$c] = [
                    'mine'   => false,
                    'count'  => 0,
                ];
            }
        }

        // Place mines randomly
        $placed = 0;
        while ($placed < $mines) {
            $r = rand(0, $rows - 1);
            $c = rand(0, $cols - 1);

            if (!$board[$r][$c]['mine']) {
                $board[$r][$c]['mine'] = true;
                $placed++;
            }
        }

        // Calculate numbers around mines
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                if ($board[$r][$c]['mine']) {
                    continue;
                }

                $count = 0;
                for ($dr = -1; $dr <= 1; $dr++) {
                    for ($dc = -1; $dc <= 1; $dc++) {
                        $nr = $r + $dr;
                        $nc = $c + $dc;

                        if ($nr >= 0 && $nr < $rows && $nc >= 0 && $nc < $cols) {
                            if ($board[$nr][$nc]['mine']) {
                                $count++;
                            }
                        }
                    }
                }
                $board[$r][$c]['count'] = $count;
            }
        }

        return $board;
    }

    public function updateTile(Game $game, int $row, int $col): array
    {
        $board = $game->board;

        if (!isset($board[$row][$col])) {
            throw new \Exception("Invalid tile coordinates");
        }

        // Reveal the tile
        $board[$row][$col]['revealed'] = true;

        // Save board back
        $game->board = $board;
        $game->save();

        return $board;
    }

}
