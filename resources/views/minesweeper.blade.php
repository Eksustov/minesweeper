<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Minesweeper') }}
        </h2>
    </x-slot>

    <div class="py-12 flex flex-col items-center space-y-4">
    <div class="bg-white shadow-md rounded-lg p-6 w-full max-w-[95vw]">
            <h1 class="text-2xl font-bold mb-4">Minesweeper</h1>

            <!-- Mine counter and status -->
            <div class="flex justify-between items-center mb-4">
                <div id="mineCounter" class="text-lg font-bold text-gray-700">Mines: 10</div>
                <div id="statusMessage" class="text-lg font-bold text-green-600"></div>
            </div>

            <!-- Grid container -->
            <div class="flex justify-center">
                <div id="game" class="grid gap-1"></div>
            </div>

            <!-- Restart button -->
            <button id="restartBtn" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                Restart
            </button>
        </div>
    </div>

    <script>
        const rows = {{ $rows }};
        const cols = {{ $cols }};
        const minesCount = {{ $minesCount }};
        let board = [];
        let gameOver = false;
        let flagsPlaced = 0;

        const mineCounter = document.getElementById('mineCounter');
        const statusMessage = document.getElementById('statusMessage');

        function initGame() {
            const game = document.getElementById('game');
            game.style.gridTemplateColumns = `repeat(${cols}, 3rem)`;
            game.innerHTML = '';
            board = [];
            gameOver = false;
            flagsPlaced = 0;
            statusMessage.textContent = '';
            mineCounter.textContent = `Mines: ${minesCount}`;

            // Initialize board
            for (let r = 0; r < rows; r++) {
                board[r] = [];
                for (let c = 0; c < cols; c++) {
                    const cell = document.createElement('button');
                    cell.className = 'w-12 h-12 bg-gray-300 rounded flex items-center justify-center text-sm font-bold';
                    cell.dataset.row = r;
                    cell.dataset.col = c;

                    // Left click = reveal
                    cell.addEventListener('click', () => reveal(r, c));

                    // Right click = toggle flag
                    cell.addEventListener('contextmenu', (e) => {
                        e.preventDefault();
                        toggleFlag(r, c);
                    });

                    game.appendChild(cell);

                    board[r][c] = { element: cell, mine: false, revealed: false, flagged: false, count: 0 };
                }
            }

            // Place mines randomly
            let placed = 0;
            while (placed < minesCount) {
                const r = Math.floor(Math.random() * rows);
                const c = Math.floor(Math.random() * cols);
                if (!board[r][c].mine) {
                    board[r][c].mine = true;
                    placed++;
                }
            }

            // Count adjacent mines
            for (let r = 0; r < rows; r++) {
                for (let c = 0; c < cols; c++) {
                    if (board[r][c].mine) continue;
                    let count = 0;
                    for (let dr = -1; dr <= 1; dr++) {
                        for (let dc = -1; dc <= 1; dc++) {
                            if (board[r+dr]?.[c+dc]?.mine) count++;
                        }
                    }
                    board[r][c].count = count;
                }
            }
        }

        function toggleFlag(r, c) {
            if (gameOver) return;
            const cell = board[r][c];
            if (cell.revealed) return;

            cell.flagged = !cell.flagged;
            if (cell.flagged) {
                cell.element.textContent = '🚩';
                cell.element.className = 'w-12 h-12 bg-yellow-300 rounded flex items-center justify-center text-sm font-bold';
                flagsPlaced++;
            } else {
                cell.element.textContent = '';
                cell.element.className = 'w-12 h-12 bg-gray-300 rounded flex items-center justify-center text-sm font-bold';
                flagsPlaced--;
            }

            mineCounter.textContent = `Mines: ${minesCount - flagsPlaced}`;
            checkWin();
        }

        function reveal(r, c) {
            if (gameOver) return;

            const cell = board[r][c];
            if (cell.revealed || cell.flagged) return;
            cell.revealed = true;

            if (cell.mine) {
                cell.element.className = 'w-12 h-12 bg-red-500 rounded flex items-center justify-center text-sm font-bold text-white';
                cell.element.textContent = '💣';
                gameOver = true;

                // Reveal all other mines
                for (let i = 0; i < rows; i++) {
                    for (let j = 0; j < cols; j++) {
                        if (board[i][j].mine && !board[i][j].revealed) {
                            board[i][j].element.className = 'w-12 h-12 bg-red-500 rounded flex items-center justify-center text-sm font-bold text-white';
                            board[i][j].element.textContent = '💣';
                            board[i][j].revealed = true;
                        }
                    }
                }
                statusMessage.textContent = 'Game Over!';
                return;
            }

            cell.element.className = 'w-12 h-12 bg-gray-100 rounded flex items-center justify-center text-sm font-bold text-gray-800';
            if (cell.count > 0) {
                cell.element.textContent = cell.count;
            } else {
                // Auto-reveal neighbors
                for (let dr = -1; dr <= 1; dr++) {
                    for (let dc = -1; dc <= 1; dc++) {
                        if (board[r+dr]?.[c+dc]) {
                            reveal(r+dr, c+dc);
                        }
                    }
                }
            }

            checkWin();
        }

        function checkWin() {
            if (gameOver) return;

            let safeCells = 0;
            for (let r = 0; r < rows; r++) {
                for (let c = 0; c < cols; c++) {
                    if (!board[r][c].mine && board[r][c].revealed) safeCells++;
                }
            }

            if (safeCells === rows*cols - minesCount) {
                gameOver = true;
                statusMessage.textContent = 'You Win! 🎉';
                // Reveal all mines visually
                for (let r = 0; r < rows; r++) {
                    for (let c = 0; c < cols; c++) {
                        if (board[r][c].mine) {
                            board[r][c].element.className = 'w-12 h-12 bg-green-500 rounded flex items-center justify-center text-sm font-bold text-white';
                            board[r][c].element.textContent = '💣';
                        }
                    }
                }
            }
        }

        window.addEventListener('DOMContentLoaded', initGame);
        document.getElementById('restartBtn').addEventListener('click', initGame);
    </script>
</x-app-layout>
