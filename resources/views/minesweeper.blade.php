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

    <!-- Room meta for live updates -->
    <div id="room-meta" data-room-id="{{ $room->id }}"></div>

    <script>
window.addEventListener('DOMContentLoaded', () => {
    const mineCounter = document.getElementById('mineCounter');
    const statusMessage = document.getElementById('statusMessage');
    const gameContainer = document.getElementById('game');
    const restartBtn = document.getElementById('restartBtn');
    const roomMeta = document.getElementById('room-meta');
    const roomId = roomMeta.dataset.roomId;

    axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

    const rows = {{ $rows }};
    const cols = {{ $cols }};
    const mines = {{ $mines }};
    const initialBoard = @json(json_decode($board));

    let board = [];
    let gameOver = false;
    let flagsPlaced = 0;

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

        // Send flag update
        window.axios.post('/games/update', {
            roomId: roomId,
            row: r,
            col: c,
            action: 'flag',
            value: cell.flagged
        });

        checkWin();
    }

    function reveal(r, c) {
        if (gameOver) return;
        const toReveal = floodReveal(r, c);
        if (!toReveal.length) return;

        // Update local board
        toReveal.forEach(({ row, col, mine, count }) => {
            const cell = board[row][col];
            if (!cell) return;
            cell.revealed = true;
            if (mine) {
                cell.element.textContent = '💣';
                cell.element.className = 'w-12 h-12 bg-red-500 rounded flex items-center justify-center text-sm font-bold text-white';
            } else {
                cell.element.textContent = count > 0 ? count : '';
                cell.element.className = 'w-12 h-12 bg-gray-100 rounded flex items-center justify-center text-sm font-bold text-gray-800';
            }
        });

        // Check for game over
        if (toReveal.some(c => c.mine)) {
            gameOver = true;
            statusMessage.textContent = 'Game Over!';
            board.flat().forEach(c => {
                if (c.mine && !c.revealed) {
                    c.element.textContent = '💣';
                    c.element.className = 'w-12 h-12 bg-red-500 rounded flex items-center justify-center text-sm font-bold text-white';
                    c.revealed = true;
                }
            });
        }

        // Send all revealed cells at once
        window.axios.post('/games/update', {
            roomId: roomId,
            action: 'reveal',
            value: toReveal
        });

        checkWin();
    }

    // Flood reveal helper (BFS)
    function floodReveal(r, c) {
        const queue = [];
        const revealed = [];
        const visited = Array.from({ length: rows }, () => Array(cols).fill(false));
        queue.push({ r, c });

        while (queue.length) {
            const { r: row, c: col } = queue.shift();
            if (!board[row] || !board[row][col]) continue;
            const cell = board[row][col];
            if (visited[row][col] || cell.revealed || cell.flagged) continue;

            visited[row][col] = true;
            revealed.push({ row, col, mine: cell.mine, count: cell.count });

            if (cell.count === 0 && !cell.mine) {
                for (let dr = -1; dr <= 1; dr++) {
                    for (let dc = -1; dc <= 1; dc++) {
                        const nr = row + dr, nc = col + dc;
                        if (board[nr]?.[nc] && !visited[nr][nc]) queue.push({ r: nr, c: nc });
                    }
                }
            }
        }

        return revealed;
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

    // Listen for live updates
    window.Echo.channel(`room.${roomId}`)
        .listen('.TileUpdated', (e) => {
            console.log('TileUpdated received', e);
            if (e.action === 'flag') {
                const cell = board[e.row]?.[e.col];
                if (!cell) return;
                cell.flagged = e.value;
                cell.element.textContent = e.value ? '🚩' : '';
                cell.element.className = e.value 
                    ? 'w-12 h-12 bg-yellow-300 rounded flex items-center justify-center text-sm font-bold'
                    : 'w-12 h-12 bg-gray-300 rounded flex items-center justify-center text-sm font-bold';
            } else if (e.action === 'reveal') {
                // For multiple revealed cells
                e.value.forEach(({ row, col, mine, count }) => {
                    const cell = board[row]?.[col];
                    if (!cell || cell.revealed) return;
                    cell.revealed = true;
                    if (mine) {
                        cell.element.textContent = '💣';
                        cell.element.className = 'w-12 h-12 bg-red-500 rounded flex items-center justify-center text-sm font-bold text-white';
                    } else {
                        cell.element.textContent = count > 0 ? count : '';
                        cell.element.className = 'w-12 h-12 bg-gray-100 rounded flex items-center justify-center text-sm font-bold text-gray-800';
                    }
                });
            }
        });

    initGame();
    restartBtn.addEventListener('click', initGame);
});
</script>

</x-app-layout>
