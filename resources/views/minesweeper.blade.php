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
                <div id="mineCounter" class="text-lg font-bold text-gray-700">Mines: 0</div>
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
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
        const rows = {{ $rows }};
        const cols = {{ $cols }};
        const mines = {{ $mines }};
        const initialBoard = @json(json_decode($board));

        let board = [];
        let gameOver = false;
        let flagsPlaced = 0;

        const mineCounter = document.getElementById('mineCounter');
        const statusMessage = document.getElementById('statusMessage');
        const gameContainer = document.getElementById('game');
        const restartBtn = document.getElementById('restartBtn');

        function initGame() {
            gameContainer.style.gridTemplateColumns = `repeat(${cols}, 3rem)`;
            gameContainer.innerHTML = '';

            board = initialBoard.map(row =>
                row.map(cell => ({
                    ...cell,
                    element: null,
                    revealed: false,
                    flagged: false
                }))
            );

            flagsPlaced = 0;
            gameOver = false;
            statusMessage.textContent = '';
            mineCounter.textContent = `Mines: ${mines}`;

            for (let r = 0; r < rows; r++) {
                for (let c = 0; c < cols; c++) {
                    const cellBtn = document.createElement('button');
                    cellBtn.className = 'w-12 h-12 bg-gray-300 rounded flex items-center justify-center text-sm font-bold';
                    cellBtn.dataset.row = r;
                    cellBtn.dataset.col = c;

                    cellBtn.addEventListener('click', () => reveal(r, c));
                    cellBtn.addEventListener('contextmenu', (e) => {
                        e.preventDefault();
                        toggleFlag(r, c);
                    });

                    gameContainer.appendChild(cellBtn);
                    board[r][c].element = cellBtn;
                }
            }
        }

        function toggleFlag(r, c) {
            if (gameOver) return;
            const cell = board[r][c];
            if (!cell || cell.revealed) return;

            cell.flagged = !cell.flagged;
            cell.element.textContent = cell.flagged ? '🚩' : '';
            cell.element.className = cell.flagged 
                ? 'w-12 h-12 bg-yellow-300 rounded flex items-center justify-center text-sm font-bold'
                : 'w-12 h-12 bg-gray-300 rounded flex items-center justify-center text-sm font-bold';

            flagsPlaced += cell.flagged ? 1 : -1;
            mineCounter.textContent = `Mines: ${mines - flagsPlaced}`;

            // Send flag update to server for synchronization
            window.axios.post('/games/update', {
                roomId: {{ $room->id }},
                row: r,
                col: c,
                action: 'flag',
                value: cell.flagged
            });

            checkWin();
        }

        function reveal(r, c) {
            if (gameOver) return;
            const cell = board[r][c];
            if (!cell || cell.revealed || cell.flagged) return;

            cell.revealed = true;

            if (cell.mine) {
                cell.element.className = 'w-12 h-12 bg-red-500 rounded flex items-center justify-center text-sm font-bold text-white';
                cell.element.textContent = '💣';
                gameOver = true;

                // Reveal all mines
                board.forEach(row => row.forEach(c => {
                    if (c.mine && !c.revealed) {
                        c.element.className = 'w-12 h-12 bg-red-500 rounded flex items-center justify-center text-sm font-bold text-white';
                        c.element.textContent = '💣';
                        c.revealed = true;
                    }
                }));

                statusMessage.textContent = 'Game Over!';

                // Send reveal to server
                window.axios.post('/games/update', {
                    roomId: {{ $room->id }},
                    row: r,
                    col: c,
                    action: 'reveal',
                    value: { mine: cell.mine, count: cell.count }
                });
                return;
            }

            cell.element.className = 'w-12 h-12 bg-gray-100 rounded flex items-center justify-center text-sm font-bold text-gray-800';
            if (cell.count > 0) {
                cell.element.textContent = cell.count;
            } else {
                // Flood reveal neighbors
                for (let dr = -1; dr <= 1; dr++) {
                    for (let dc = -1; dc <= 1; dc++) {
                        if (board[r + dr]?.[c + dc]) reveal(r + dr, c + dc);
                    }
                }
            }

            checkWin();
        }

        function checkWin() {
            if (gameOver) return;

            const safeCells = board.flat().filter(c => !c.mine && c.revealed).length;
            if (safeCells === rows * cols - mines) {
                gameOver = true;
                statusMessage.textContent = 'You Win! 🎉';
                board.flat().forEach(c => {
                    if (c.mine) {
                        c.element.className = 'w-12 h-12 bg-green-500 rounded flex items-center justify-center text-sm font-bold text-white';
                        c.element.textContent = '💣';
                    }
                });
            }
        }

        const roomMeta = document.getElementById('room-meta');
        if (roomMeta) {
            const roomId = roomMeta.dataset.roomId;

            window.Echo.channel(`room.${roomId}`)
                .listen('.TileUpdated', (e) => {
                    const cell = board[e.row][e.col];
                    if (!cell) return;

                    if (e.action === 'flag') {
                        cell.flagged = e.value;
                        cell.element.textContent = e.value ? '🚩' : '';
                        cell.element.className = e.value 
                            ? 'w-12 h-12 bg-yellow-300 rounded flex items-center justify-center text-sm font-bold'
                            : 'w-12 h-12 bg-gray-300 rounded flex items-center justify-center text-sm font-bold';
                    } else if (e.action === 'reveal') {
                        cell.revealed = true;
                        if (e.value.mine) {
                            cell.element.textContent = '💣';
                            cell.element.className = 'w-12 h-12 bg-red-500 rounded flex items-center justify-center text-sm font-bold text-white';
                        } else {
                            cell.element.textContent = e.value.count > 0 ? e.value.count : '';
                            cell.element.className = 'w-12 h-12 bg-gray-100 rounded flex items-center justify-center text-sm font-bold text-gray-800';
                        }
                    }
                });
        }


        window.addEventListener('DOMContentLoaded', initGame);
        restartBtn.addEventListener('click', initGame);
    </script>

</x-app-layout>
