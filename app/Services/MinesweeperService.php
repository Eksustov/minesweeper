<?php

namespace App\Services;

class MinesweeperService
{
    public static function generateBoard(int $rows, int $cols, int $mines): array
    {
        $board = [];

        // Initialize empty board
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                $board[$r][$c] = [
                    'mine' => false,
                    'count' => 0,
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

        // Count adjacent mines
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                if ($board[$r][$c]['mine']) continue;

                $count = 0;
                for ($dr = -1; $dr <= 1; $dr++) {
                    for ($dc = -1; $dc <= 1; $dc++) {
                        $nr = $r + $dr;
                        $nc = $c + $dc;
                        if (isset($board[$nr][$nc]) && $board[$nr][$nc]['mine']) {
                            $count++;
                        }
                    }
                }
                $board[$r][$c]['count'] = $count;
            }
        }

        return $board;
    }
}
